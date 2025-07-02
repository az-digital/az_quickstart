<?php

/**
 * @file
 * Post update functions for Paragraphs.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Set the parent id, type and field name to the already created paragraphs.
 *
 * @param $sandbox
 */
function paragraphs_post_update_set_paragraphs_parent_fields(&$sandbox) {
  // Don't execute the function if paragraphs_update_8003() was already executed
  // which used to do the same.

  $module_schema = \Drupal::service('update.update_hook_registry')->getInstalledVersion('paragraphs');

  // The state entry 'paragraphs_update_8003_placeholder' is used in order to
  // indicate that the placeholder paragraphs_update_8003() function has been
  // executed, so this function needs to be executed as well. If the non
  // placeholder version of paragraphs_update_8003() got executed already, the
  // state won't be set and we skip this update.
  if ($module_schema >= 8003 && !\Drupal::state()->get('paragraphs_update_8003_placeholder', FALSE)) {
    return;
  }

  if (!isset($sandbox['current_paragraph_field_id'])) {
    $paragraph_field_ids = [];
    // Get all the entity reference revisions fields.
    $map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference_revisions');
    foreach ($map as $entity_type_id => $info) {
      foreach ($info as $name => $data) {
        if (FieldStorageConfig::loadByName($entity_type_id, $name)->getSetting('target_type') == 'paragraph') {
          $paragraph_field_ids[] = "$entity_type_id.$name";
        }
      }
    }

    if (!$paragraph_field_ids) {
      // There are no paragraph fields. Return before initializing the sandbox.
      return;
    }

    // Initialize the sandbox.
    $sandbox['current_paragraph_field_id'] = 0;
    $sandbox['paragraph_field_ids'] = $paragraph_field_ids;
    $sandbox['max'] = count($paragraph_field_ids);
    $sandbox['progress'] = 0;
  }

  /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
  $field_storage = FieldStorageConfig::load($sandbox['paragraph_field_ids'][$sandbox['current_paragraph_field_id']]);
  // For revisionable entity types, we load and update all revisions.
  $target_entity_type = \Drupal::entityTypeManager()->getDefinition($field_storage->getTargetEntityTypeId());
  if ($target_entity_type->isRevisionable()) {
    $revision_id = $target_entity_type->getKey('revision');
    $entity_ids = \Drupal::entityQuery($field_storage->getTargetEntityTypeId())
      ->condition($field_storage->getName(), NULL, 'IS NOT NULL')
      ->range($sandbox['progress'], Settings::get('paragraph_limit', 50))
      ->allRevisions()
      ->sort($revision_id, 'ASC')
      ->accessCheck(FALSE)
      ->execute();
  }
  else {
    $id = $target_entity_type->getKey('id');
    $entity_ids = \Drupal::entityQuery($field_storage->getTargetEntityTypeId())
      ->condition($field_storage->getName(), NULL, 'IS NOT NULL')
      ->range($sandbox['progress'], Settings::get('paragraph_limit', 50))
      ->sort($id, 'ASC')
      ->accessCheck(FALSE)
      ->execute();
  }
  foreach ($entity_ids as $revision_id => $entity_id) {
    // For revisionable entity types, we load a specific revision otherwise load
    // the entity.
    if ($target_entity_type->isRevisionable()) {
      $host_entity = \Drupal::entityTypeManager()
        ->getStorage($field_storage->getTargetEntityTypeId())
        ->loadRevision($revision_id);
    }
    else {
      $host_entity = \Drupal::entityTypeManager()
        ->getStorage($field_storage->getTargetEntityTypeId())
        ->load($entity_id);
    }
    foreach ($host_entity->get($field_storage->getName()) as $field_item) {
      // Skip broken and already updated references (e.g. Nested paragraphs).
      if ($field_item->entity && empty($field_item->entity->parent_type->value)) {
        // Set the parent fields and save, ensure that no new revision is
        // created.
        $field_item->entity->parent_type = $field_storage->getTargetEntityTypeId();
        $field_item->entity->parent_id = $host_entity->id();
        $field_item->entity->parent_field_name = $field_storage->getName();
        $field_item->entity->setNewRevision(FALSE);
        $field_item->entity->save();
      }
    }
  }
  // Continue with the next paragraph_field_id when the loaded entities are less
  // than paragraph_limit.
  if (count($entity_ids) < Settings::get('paragraph_limit', 50)) {
    $sandbox['current_paragraph_field_id']++;
    $sandbox['progress'] = 0;
  }
  else {
    $sandbox['progress'] += Settings::get('paragraph_limit', 50);
  }
  // Update #finished, 1 if the whole update has finished.
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current_paragraph_field_id'] / $sandbox['max']);
}

/**
 * Update the parent fields with revisionable data.
 */
function paragraphs_post_update_rebuild_parent_fields(array &$sandbox) {
  $database = \Drupal::database();
  $entity_type_manager = \Drupal::entityTypeManager();
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
  $paragraph_revisions_data_table = $entity_type_manager->getDefinition('paragraph')->getRevisionDataTable();
  $paragraph_storage = $entity_type_manager->getStorage('paragraph');

  if (!isset($sandbox['current_index'])) {
    $entity_reference_revisions_fields = $entity_field_manager->getFieldMapByFieldType('entity_reference_revisions');
    $paragraph_field_ids = [];
    foreach ($entity_reference_revisions_fields as $entity_type_id => $fields) {
      // Skip non-revisionable entity types.
      $entity_type_definition = $entity_definition_update_manager->getEntityType($entity_type_id);
      if (!$entity_type_definition || !$entity_type_definition->isRevisionable()) {
        continue;
      }

      // Skip non-SQL entity storage implementations.
      $storage = $entity_type_manager->getStorage($entity_type_id);
      if (!$storage instanceof SqlEntityStorageInterface) {
        continue;
      }

      $storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
      $storage_definitions = array_intersect_key($storage_definitions, $fields);

      // Process the fields that reference paragraphs.
      $storage_definitions = array_filter($storage_definitions, function (FieldStorageDefinitionInterface $field_storage) {
        return $field_storage->getSetting('target_type') === 'paragraph';
      });

      foreach ($storage_definitions as $field_name => $field_storage) {
        // Get the field revision table name.
        $table_mapping = $storage->getTableMapping();
        $column_names = $table_mapping->getColumnNames($field_name);
        $revision_column = $column_names['target_revision_id'];

        if ($field_storage instanceof BaseFieldDefinition && $field_storage->getCardinality() === 1) {
          $field_revision_table = $storage->getRevisionDataTable() ?: $storage->getRevisionTable();
          $entity_id_column = $entity_type_definition->getKey('id');
        }
        else {
          $field_revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage);
          $entity_id_column = 'entity_id';
        }

        // Build a data array of the needed data to do the query.
        $data = [
          'entity_type_id' => $entity_type_id,
          'field_name' => $field_name,
          'revision_table' => $field_revision_table,
          'entity_id_column' => $entity_id_column,
          'revision_column' => $revision_column,
          'langcode_column' => $entity_type_definition->getKey('langcode'),
        ];

        // Nested paragraphs must be updated first.
        if ($entity_type_id === 'paragraph') {
          array_unshift($paragraph_field_ids, $data);
        }
        else {
          $paragraph_field_ids[] = $data;
        }
      }
    }

    if (empty($paragraph_field_ids)) {
      // There are no paragraph fields. Return before initializing the sandbox.
      return;
    }

    // Initialize the sandbox.
    $sandbox['current_index'] = 0;
    $sandbox['paragraph_field_ids'] = $paragraph_field_ids;
    $sandbox['max'] = count($paragraph_field_ids);
    $sandbox['max_revision_id'] = NULL;
  }

  $current_field = $sandbox['paragraph_field_ids'][$sandbox['current_index']];
  $revision_column = !empty($current_field['revision_column']) ? ($current_field['revision_column']) : $current_field['field_name'] . '_target_revision_id';
  $entity_id_column = $current_field['entity_id_column'];
  $entity_type_id = $current_field['entity_type_id'];
  $field_name = $current_field['field_name'];

  // Select the field values from the revision of the parent entity type.
  $query = $database->select($current_field['revision_table'], 'f');

  // Join tables by paragraph revision IDs.
  $query->innerJoin($paragraph_revisions_data_table, 'p', "f.$revision_column = p.revision_id");
  $query->fields('f', [$entity_id_column, $revision_column]);

  // Select paragraphs with at least one wrong parent field.
  $or_group = new Condition('OR');
  // Only use CAST if the db driver is Postgres.
  if (Database::getConnection()->databaseType() == 'pgsql') {
    $or_group->where("CAST(p.parent_id as TEXT) <> CAST(f.$entity_id_column as TEXT)");
  }
  else {
    $or_group->where("p.parent_id <> f.$entity_id_column");
  }
  $or_group->condition('p.parent_type', $entity_type_id, '<>');
  $or_group->condition('p.parent_field_name', $field_name, '<>');
  $query->condition($or_group);

  // Match the langcode so we can deal with revisions translations.
  if (!empty($current_field['langcode_column'])) {
    $query->where('p.langcode = f.' . $current_field['langcode_column']);
  }

  // Order the query by revision ID and limit the number of results.
  $query->orderBy('p.revision_id');

  // Only check the revisions that are not already processed.
  if ($sandbox['max_revision_id']) {
    $query->condition('p.revision_id', $sandbox['max_revision_id'], '>');
  }
  // Limit the number of processed paragraphs per run.
  $query->range(0, Settings::get('paragraph_limit', 100));

  $results = $query->execute()->fetchAll();

  // Update the parent fields of the identified paragraphs revisions.
  foreach ($results as $result) {
    /** @var \Drupal\paragraphs\ParagraphInterface $revision */
    $revision = $paragraph_storage->loadRevision($result->$revision_column);
    if ($revision) {
      $revision->set('parent_id', $result->$entity_id_column);
      $revision->set('parent_type', $entity_type_id);
      $revision->set('parent_field_name', $field_name);
      $revision->save();
    }
  }

  // Continue with the next element in case we processed all the paragraphs
  // assigned to the current paragraph field.
  if (count($results) < Settings::get('paragraph_limit', 100)) {
    $sandbox['current_index']++;
    $sandbox['max_revision_id'] = NULL;
  }
  else {
    $last_revision_result = end($results);
    $sandbox['max_revision_id'] = $last_revision_result->$revision_column;
  }

  // Update finished key if the whole update has finished.
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current_index'] / $sandbox['max']);
}
