<?php

/**
 * @file
 * Post update functions for Metatag.
 */

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Utility\UpdateException;
use Drupal\metatag\Entity\MetatagDefaults;

/**
 * Get a list of all metatag field tables.
 *
 * @return array
 *   A list of meta tag field tables with the table name as the key and the
 *   field value column as the value, e.g.:
 *   - node__field_meta_tags: field_meta_tags_value
 *   - node_revision__field_meta_tags: field_meta_tags_value
 */
function _metatag_list_entity_field_tables(): array {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast[__FUNCTION__] = &drupal_static(__FUNCTION__);
  }
  $tables = &$drupal_static_fast[__FUNCTION__];

  if (is_null($tables)) {
    $tables = [];
    $entity_type_manager = \Drupal::entityTypeManager();
    $database = \Drupal::database();

    // Get all of the field storage entities of type metatag.
    /** @var \Drupal\field\FieldStorageConfigInterface[] $field_storage_configs */
    $field_storage_configs = $entity_type_manager
      ->getStorage('field_storage_config')
      ->loadByProperties(['type' => 'metatag']);

    foreach ($field_storage_configs as $field_storage) {
      $field_name = $field_storage->getName();

      // Get the individual fields (field instances) associated with bundles.
      // This query can result in an exception if a field configuration is
      // faulty.
      // @see https://www.drupal.org/project/metatag/issues/3366933
      try {
        $fields = $entity_type_manager
          ->getStorage('field_config')
          ->loadByProperties([
            'field_name' => $field_name,
            'entity_type' => $field_storage->getTargetEntityTypeId(),
          ]);
      }
      catch (PluginNotFoundException $e) {
        throw new \Exception("There is a problem in the field configuration, see https://www.drupal.org/node/3366933 for discussion on how to resolve it.\nOriginal message: " . $e->getMessage());
      }

      $tables = [];
      foreach ($fields as $field) {
        $entity_type_id = $field->getTargetEntityTypeId();
        $entity_type = $entity_type_manager->getDefinition($entity_type_id);

        // Determine the table and "value" field names.
        /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
        $table_mapping = $entity_type_manager->getStorage($entity_type_id)
          ->getTableMapping();
        $field_table = $table_mapping->getFieldTableName($field_name);
        $field_value_field = $table_mapping->getFieldColumnName($field_storage, 'value');

        $tables[$field_table] = $field_value_field;
        if ($entity_type->isRevisionable() && $field_storage->isRevisionable()) {
          if ($table_mapping->requiresDedicatedTableStorage($field_storage)) {
            $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
            if ($database->schema()->tableExists($revision_table)) {
              $tables[$revision_table] = $field_value_field;
            }
          }
          elseif ($table_mapping->allowsSharedTableStorage($field_storage)) {
            $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
            if ($database->schema()->tableExists($revision_table)) {
              $tables[$revision_table] = $field_value_field;
            }
          }
        }
      }
    }
  }

  return $tables;
}

/**
 * Convert mask-icon to array values.
 */
function metatag_post_update_convert_mask_icon_to_array_values(&$sandbox) {
  $updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $default) {
    if ($default->hasTag('mask-icon')) {
      $tags = $default->get('tags');
      $tags['mask_icon'] = [
        'href' => $default->getTag('mask-icon'),
      ];
      unset($tags['mask-icon']);
      $default->set('tags', $tags);
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * The author meta tag was moved into the main module: configuration.
 */
function metatag_post_update_convert_author_config(&$sandbox) {
  $updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $default) {
    if ($default->hasTag('google_plus_author')) {
      $tags = $default->get('tags');
      $tags['author'] = $default->getTag('google_plus_author');
      unset($tags['google_plus_author']);
      $default->set('tags', $tags);
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * The author meta tag was moved into the main module: entity data.
 */
function metatag_post_update_convert_author_data(&$sandbox) {
  $database = \Drupal::database();

  // This whole top section only needs to be done the first time.
  if (!isset($sandbox['total_records'])) {
    $sandbox['records_processed'] = 0;
    $sandbox['total_records'] = 0;
    $sandbox['current_field'] = 0;
    $sandbox['current_record'] = 0;

    // Counter to enumerate the fields so we can access them in the array
    // by number rather than name.
    $field_counter = 0;

    // Look for the appropriate data in all metatag field tables.
    foreach (_metatag_list_entity_field_tables() as $table => $field_value_field) {
      $query = $database->select($table);
      $query->addField($table, 'entity_id');
      $query->addField($table, 'revision_id');
      $query->addField($table, 'langcode');
      $query->addField($table, $field_value_field);
      $db_or = $query->orConditionGroup();
      $db_or->condition($field_value_field, '%google_plus_author%', 'LIKE');
      $query->condition($db_or);
      $result = $query->execute();
      $records = $result->fetchAll();

      if (empty($records)) {
        continue;
      }

      // Fill in all the sandbox information
      // so we can batch the individual
      // record comparing and updating.
      $sandbox['fields'][$field_counter]['field_table'] = $table;
      $sandbox['fields'][$field_counter]['field_value_field'] = $field_value_field;
      $sandbox['fields'][$field_counter]['records'] = $records;

      $sandbox['total_records'] += count($records);
      $field_counter++;
    }
  }

  if ($sandbox['total_records'] == 0) {
    // No partially overridden fields so we can skip the whole batch process.
    $sandbox['#finished'] = 1;
  }
  else {
    // Begin the batch processing of individual field records.
    $max_per_batch = 100;
    $counter = 1;

    $current_field = $sandbox['current_field'];
    $current_field_records = $sandbox['fields'][$current_field]['records'];
    $current_record = $sandbox['current_record'];

    $field_table = $sandbox['fields'][$current_field]['field_table'];
    $field_value_field = $sandbox['fields'][$current_field]['field_value_field'];

    // Loop through the field(s) and update the mask_icon values if necessary.
    while ($counter <= $max_per_batch && isset($current_field_records[$current_record])) {
      $record = $current_field_records[$current_record];

      // Strip any empty tags or ones matching the field's defaults and leave
      // only the overridden tags in $new_tags.
      $tags = unserialize($record->$field_value_field, ['allowed_classes' => FALSE]);
      if (isset($tags['google_plus_author'])) {
        $tags['author'] = $tags['google_plus_author'];
        $tags_string = serialize($tags);
        $database->update($field_table)
          ->fields([
            $field_value_field => $tags_string,
          ])
          ->condition('entity_id', $record->entity_id)
          ->condition('revision_id', $record->revision_id)
          ->condition('langcode', $record->langcode)
          ->execute();
      }
      $counter++;
      $current_record++;
    }

    // We ran out of records for the field so start the next batch out with the
    // next field.
    if (!isset($current_field_records[$current_record])) {
      $current_field++;
      $current_record = 0;
    }

    $sandbox['records_processed'] += $counter - 1;

    // We have finished all the fields. All done.
    if (!isset($sandbox['fields'][$current_field])) {
      $sandbox['#finished'] = 1;
    }
    // Update the sandbox values to prepare for the next round.
    else {
      $sandbox['current_field'] = $current_field;
      $sandbox['current_record'] = $current_record;
      $sandbox['#finished'] = $sandbox['records_processed'] / $sandbox['total_records'];
    }
  }

  if ($sandbox['total_records'] > 0) {
    return (string) t('Processed @processed of @total overridden Metatag records.', [
      '@processed' => $sandbox['records_processed'],
      '@total' => $sandbox['total_records'],
    ]);
  }

  return (string) t('There were no overridden Metatag records.');
}

/**
 * Remove 'noydir', 'noodp' ROBOTS options from meta tag entity fields.
 */
function metatag_post_update_remove_robots_noydir_noodp(&$sandbox) {
  $database = \Drupal::database();

  // This whole top section only needs to be done the first time.
  if (!isset($sandbox['total_records'])) {
    $sandbox['records_processed'] = 0;
    $sandbox['total_records'] = 0;
    $sandbox['current_field'] = 0;
    $sandbox['current_record'] = 0;

    // Counter to enumerate the fields so we can access them in the array
    // by number rather than name.
    $field_counter = 0;

    // Look for the appropriate data in all metatag field tables.
    foreach (_metatag_list_entity_field_tables() as $table => $field_value_field) {
      $query = $database->select($table);
      $query->addField($table, 'entity_id');
      $query->addField($table, 'revision_id');
      $query->addField($table, 'langcode');
      $query->addField($table, $field_value_field);
      $db_or = $query->orConditionGroup();
      $db_or->condition($field_value_field, '%noodp%', 'LIKE');
      $db_or->condition($field_value_field, '%noydir%', 'LIKE');
      $query->condition($db_or);
      $result = $query->execute();
      $records = $result->fetchAll();

      if (empty($records)) {
        continue;
      }

      // Fill in all the sandbox information so we can batch the
      // individual record comparing and updating.
      $sandbox['fields'][$field_counter]['field_table'] = $table;
      $sandbox['fields'][$field_counter]['field_value_field'] = $field_value_field;
      $sandbox['fields'][$field_counter]['records'] = $records;

      $sandbox['total_records'] += count($records);
      $field_counter++;
    }
  }

  if ($sandbox['total_records'] == 0) {
    // No partially overridden fields so we can skip the whole batch process.
    $sandbox['#finished'] = 1;
  }
  else {
    // Begin the batch processing of individual field records.
    $max_per_batch = 100;
    $counter = 1;

    $current_field = $sandbox['current_field'];
    $current_field_records = $sandbox['fields'][$current_field]['records'];
    $current_record = $sandbox['current_record'];

    $field_table = $sandbox['fields'][$current_field]['field_table'];
    $field_value_field = $sandbox['fields'][$current_field]['field_value_field'];

    // Loop through the field(s) and remove 'noydir'
    // from value where applicable.
    while ($counter <= $max_per_batch && isset($current_field_records[$current_record])) {
      $record = $current_field_records[$current_record];

      // Strip any empty tags or ones matching the field's defaults and leave
      // only the overridden tags in $new_tags.
      $tags = metatag_data_decode($record->$field_value_field);
      if (!empty($tags['robots'])) {
        if (is_array($tags['robots'])) {
          $new_robots = $old_robots = $tags['robots'];
        }
        else {
          $new_robots = $old_robots = explode(', ', $tags['robots']);
        }
        $new_robots = array_diff($new_robots, ['noodp']);
        $new_robots = array_diff($new_robots, ['noydir']);
        if ($old_robots != $new_robots) {
          $tags['robots'] = implode(', ', $new_robots);

          $tags_string = serialize($tags);
          $database->update($field_table)
            ->fields([
              $field_value_field => $tags_string,
            ])
            ->condition('entity_id', $record->entity_id)
            ->condition('revision_id', $record->revision_id)
            ->condition('langcode', $record->langcode)
            ->execute();
        }
      }
      $counter++;
      $current_record++;
    }

    // We ran out of records for the field so start the next batch out with the
    // next field.
    if (!isset($current_field_records[$current_record])) {
      $current_field++;
      $current_record = 0;
    }

    $sandbox['records_processed'] += $counter - 1;

    // We have finished all the fields. All done.
    if (!isset($sandbox['fields'][$current_field])) {
      $sandbox['#finished'] = 1;
    }
    // Update the sandbox values to prepare for the next round.
    else {
      $sandbox['current_field'] = $current_field;
      $sandbox['current_record'] = $current_record;
      $sandbox['#finished'] = $sandbox['records_processed'] / $sandbox['total_records'];
    }
  }

  if ($sandbox['total_records'] > 0) {
    return (string) t('Processed @processed of @total overridden Metatag records.', [
      '@processed' => $sandbox['records_processed'],
      '@total' => $sandbox['total_records'],
    ]);
  }
  else {
    return (string) t("There were no overridden Metatag records.");
  }
}

/**
 * Convert all fields to use JSON storage.
 */
function metatag_post_update_v2_01_change_fields_to_json(&$sandbox) {
  $database = \Drupal::database();

  // This whole top section only needs to be done the first time.
  if (!isset($sandbox['records_processed'])) {
    $sandbox['records_processed'] = 0;
    $sandbox['total_records'] = 0;
    $sandbox['current_field'] = 0;

    // Counter to enumerate the fields so we can access them in the array
    // by number rather than name.
    $field_counter = 0;

    // Look for the appropriate data in all metatag field tables.
    foreach (_metatag_list_entity_field_tables() as $table => $field_value_field) {
      // Get all records that were not converted yet.
      $query = $database->select($table);
      $query->addField($table, 'revision_id');

      // Fields with serialized arrays.
      $query->condition($field_value_field, "a:%", 'LIKE');
      $result = $query->execute();
      $records = $result->fetchCol();

      // Fill in all the sandbox information so we can batch the
      // individual record comparing and updating.
      if (!empty($records)) {
        $sandbox['fields'][$field_counter]['field_table'] = $table;
        $sandbox['fields'][$field_counter]['field_value_field'] = $field_value_field;
        $sandbox['total_records'] += (int) count($records);
        $field_counter++;
      }
    }
  }

  if ($sandbox['total_records'] === 0) {
    // No partially overridden fields so we can skip the whole batch process.
    $sandbox['#finished'] = 1;
  }
  else {
    // Begin the batch processing of individual field records.
    $max_per_batch = 100;
    $counter = 0;

    $current_field = $sandbox['current_field'];

    $field_table = $sandbox['fields'][$current_field]['field_table'];
    $field_value_field = $sandbox['fields'][$current_field]['field_value_field'];

    // Get a segment of the records for this table.
    $query = $database->select($field_table);
    $query->addField($field_table, 'entity_id');
    $query->addField($field_table, 'revision_id');
    $query->addField($field_table, 'langcode');
    $query->addField($field_table, $field_value_field);
    $query->range(0, $max_per_batch);
    // Fields with serialized arrays.
    $query->condition($field_value_field, "a:%", 'LIKE');
    $results = $query->execute();

    // Loop through the field(s) and update the serialized values.
    foreach ($results as $record) {
      // @todo Delete records that are empty.
      // @todo Strip empty tags.
      // @todo Remove tags matching the defaults and leave overridden values.
      if (substr($record->$field_value_field, 0, 2) === 'a:') {
        $tags = @unserialize($record->$field_value_field, ['allowed_classes' => FALSE]);

        if (is_array($tags)) {
          $tags_string = Json::encode($tags);
          $database->update($field_table)
            ->fields([
              $field_value_field => $tags_string,
            ])
            ->condition('entity_id', $record->entity_id)
            ->condition('revision_id', $record->revision_id)
            ->condition('langcode', $record->langcode)
            ->execute();
        }
        $counter++;
      }
      else {
        throw new UpdateException("It seems like there was a problem with the data. The update script should probably be improved to better handle these scenarios.");
      }
    }
    if (empty($counter)) {
      $sandbox['current_field']++;
    }

    $sandbox['records_processed'] += $counter;

    // We have finished all the fields. All done.
    if ($sandbox['records_processed'] >= $sandbox['total_records']) {
      $sandbox['#finished'] = 1;
    }
    // Update the sandbox values to prepare for the next round.
    else {
      $sandbox['#finished'] = $sandbox['records_processed'] / $sandbox['total_records'];
    }
  }

  if ($sandbox['total_records'] > 0) {
    return (string) t('Processed @processed of @total overridden Metatag records.', [
      '@processed' => $sandbox['records_processed'],
      '@total' => $sandbox['total_records'],
    ]);
  }
  else {
    return (string) t("There were no overridden Metatag records that needed to be updated to store the data using JSON.");
  }
}

/**
 * Remove meta tags entity values that were removed in v2.
 */
function metatag_post_update_v2_02_remove_entity_values(array &$sandbox) {
  $database = \Drupal::database();

  $metatags_to_remove = [
    // For #3065441.
    'google_plus_author',
    'google_plus_description',
    'google_plus_name',
    'google_plus_publisher',

    // For #2973351.
    'news_keywords',
    'standout',

    // For #3132065.
    'twitter_cards_data1',
    'twitter_cards_data2',
    'twitter_cards_dnt',
    'twitter_cards_gallery_image0',
    'twitter_cards_gallery_image1',
    'twitter_cards_gallery_image2',
    'twitter_cards_gallery_image3',
    'twitter_cards_image_height',
    'twitter_cards_image_width',
    'twitter_cards_label1',
    'twitter_cards_label2',
    'twitter_cards_page_url',

    // For #3217263.
    'content_language',

    // For #3361816.
    'google_rating',
  ];
  // Twitter Card Type values that need to be changed for #3132062.
  $twitter_type_changes = [
    'photo' => 'summary_large_image',
    'gallery' => 'summary_large_image',
    'product' => 'summary',
  ];

  // This whole top section only needs to be done the first time.
  if (!isset($sandbox['records_processed'])) {
    $sandbox['records_processed'] = 0;
    $sandbox['total_records'] = 0;
    $sandbox['current_field'] = 0;

    // Counter to enumerate the fields so we can access them in the array
    // by number rather than name.
    $field_counter = 0;

    // Look for the appropriate data in all metatag field tables.
    foreach (_metatag_list_entity_field_tables() as $table => $field_value_field) {
      // Get all records that were not converted yet.
      $query = $database->select($table);
      $query->addField($table, 'revision_id');

      // Only look for Metatag field records that have the meta tags that
      // are being removed.
      $db_or = $query->orConditionGroup();
      foreach ($metatags_to_remove as $tag_name) {
        $db_or->condition($field_value_field, '%"' . $tag_name . '"%', 'LIKE');
      }

      // Look for Twitter Card "type" values, those might need to be
      // changed.
      foreach ($twitter_type_changes as $type_from => $type_to) {
        $db_or->condition($field_value_field, '%"twitter_cards_type":"' . $type_from . '"%', 'LIKE');
      }

      $query->condition($db_or);
      $result = $query->execute();
      $records = $result->fetchCol();

      // Fill in all the sandbox information so we can batch the
      // individual record comparing and updating.
      if (!empty($records)) {
        $sandbox['fields'][$field_counter]['field_table'] = $table;
        $sandbox['fields'][$field_counter]['field_value_field'] = $field_value_field;
        $sandbox['total_records'] += (int) count($records);
        $field_counter++;
      }
    }
  }

  if ($sandbox['total_records'] === 0) {
    // No partially overridden fields so we can skip the whole batch process.
    $sandbox['#finished'] = 1;
  }
  else {
    // Begin the batch processing of individual field records.
    $max_per_batch = 100;
    $counter = 0;

    $current_field = $sandbox['current_field'];

    $field_table = $sandbox['fields'][$current_field]['field_table'];
    $field_value_field = $sandbox['fields'][$current_field]['field_value_field'];

    // Get a segment of the records for this table.
    $query = $database->select($field_table);
    $query->addField($field_table, 'entity_id');
    $query->addField($field_table, 'revision_id');
    $query->addField($field_table, 'langcode');
    $query->addField($field_table, $field_value_field);
    $query->range(0, $max_per_batch);

    // Only look for Metatag field records that have the meta tags that
    // are being removed.
    $db_or = $query->orConditionGroup();
    foreach ($metatags_to_remove as $tag_name) {
      $db_or->condition($field_value_field, '%"' . $tag_name . '"%', 'LIKE');
    }

    // Look for Twitter Card "type" values, those might need to be changed.
    foreach ($twitter_type_changes as $type_from => $type_to) {
      $db_or->condition($field_value_field, '%"twitter_cards_type":"' . $type_from . '"%', 'LIKE');
    }

    $query->condition($db_or);
    $results = $query->execute();

    // Loop through the field(s) and remove the two meta tags.
    foreach ($results as $record) {
      $tags = metatag_data_decode($record->$field_value_field);
      $changed = FALSE;

      // Remove some of the meta tags.
      foreach ($metatags_to_remove as $metatag) {
        if (isset($tags[$metatag])) {
          unset($tags[$metatag]);
          $changed = TRUE;
        }
      }

      // Look for Twitter Card "type" values, those might need to be changed.
      if (isset($tags['twitter_cards_type'])) {
        foreach ($twitter_type_changes as $type_from => $type_to) {
          if ($tags['twitter_cards_type'] == $type_from) {
            $tags['twitter_cards_type'] = $type_to;
            $changed = TRUE;
            break;
          }
        }
      }

      if ($changed) {
        $tags_string = Json::encode($tags);
        $database->update($field_table)
          ->fields([
            $field_value_field => $tags_string,
          ])
          ->condition('entity_id', $record->entity_id)
          ->condition('revision_id', $record->revision_id)
          ->condition('langcode', $record->langcode)
          ->execute();
      }
      $counter++;
    }
    if (empty($counter)) {
      $sandbox['current_field']++;
    }
    else {
      $sandbox['records_processed'] += $counter;
    }

    // We have finished all the fields. All done.
    if ($sandbox['records_processed'] >= $sandbox['total_records']) {
      $sandbox['#finished'] = 1;
    }
    // Update the sandbox values to prepare for the next round.
    else {
      $sandbox['#finished'] = $sandbox['records_processed'] / $sandbox['total_records'];
    }
  }

  if ($sandbox['total_records'] > 0) {
    return (string) t('Processed @processed of @total updating Metatag records with the Publisher or Name meta tags.', [
      '@processed' => $sandbox['records_processed'],
      '@total' => $sandbox['total_records'],
    ]);
  }
  else {
    return (string) t("There were no Metatag records to update.");
  }
}

/**
 * Remove meta tags from default configurations that were removed in v2.
 */
function metatag_post_update_v2_03_remove_config_values(&$sandbox) {
  $updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $default) {
    $metatags_to_remove = [
      // For #3065441.
      'google_plus_author',
      'google_plus_description',
      'google_plus_name',
      'google_plus_publisher',

      // For #2973351.
      'news_keywords',
      'standout',

      // For #3132065.
      'twitter_cards_data1',
      'twitter_cards_data2',
      'twitter_cards_dnt',
      'twitter_cards_gallery_image0',
      'twitter_cards_gallery_image1',
      'twitter_cards_gallery_image2',
      'twitter_cards_gallery_image3',
      'twitter_cards_image_height',
      'twitter_cards_image_width',
      'twitter_cards_label1',
      'twitter_cards_label2',
      'twitter_cards_page_url',

      // For #3217263.
      'content_language',

      // For #3361816.
      'google_rating',
    ];
    $changed = FALSE;
    $tags = $default->get('tags');
    foreach ($metatags_to_remove as $metatag) {
      if (isset($tags[$metatag])) {
        unset($tags[$metatag]);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $default->set('tags', $tags);
      \Drupal::logger('metatag')
        ->notice(t('Removed meta tags from the @config Metatag configuration.', [
          '@config' => $default->id(),
        ]));
    }
    return $changed;
  });
}

/**
 * Uninstall submodule(s) deprecated in v2: GooglePlus.
 */
function metatag_post_update_v2_04_uninstall_modules() {
  $moduleHandler = \Drupal::moduleHandler();

  if (!$moduleHandler->moduleExists('metatag_google_plus')) {
    return (string) t("Metatag: Google Plus is not enabled, nothing to do.");
  }

  /** @var \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller */
  $moduleInstaller = \Drupal::service('module_installer');
  $moduleInstaller->uninstall(['metatag_google_plus']);

  return (string) t("Metatag: Google Plus has been uninstalled.");
}

/**
 * Replace deprecated/removed Twitter Card "type" values.
 */
function metatag_post_update_v2_05_twitter_type_changes(&$sandbox) {
  $updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $default) {
    // Twitter Card "type" values that need to be changed for #3132062.
    $twitter_type_changes = [
      'photo' => 'summary_large_image',
      'gallery' => 'summary_large_image',
      'product' => 'summary',
    ];
    $changed = FALSE;
    if ($default->hasTag('twitter_cards_type')) {
      $tags = $default->get('tags');
      foreach ($twitter_type_changes as $type_from => $type_to) {
        if ($tags['twitter_cards_type'] != $type_from) {
          continue;
        }
        $tags['twitter_cards_type'] = $type_to;
        $changed = TRUE;
        break;
      }
      if ($changed) {
        $default->set('tags', $tags);
        \Drupal::logger('metatag')
          ->notice(t('Corrected the Twitter Card "type" value in the @config Metatag configuration.', [
            '@config' => $default->id(),
          ]));
      }
    }
    return $changed;
  });
}
