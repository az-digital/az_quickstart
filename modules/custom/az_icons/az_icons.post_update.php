<?php

/**
 * @file
 * Post update functions for az_icons module.
 */

use Drupal\az_icons\Utility\AZIconColorNames;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Rename the Arizona Bootstrap color names saved in icons.
 *
 * Arizona Bootstrap 5.3.0 renamed its brand colors (for example, Sky is now
 * Rain). An icon saves its color in formatted text, for example
 * <drupal-icon data-icon-id="material_symbols:home"
 * data-icon-settings='{"color":"sky"}'>. This renames the color of every
 * arizona_icons and material_symbols icon, in every formatted text field,
 * including fields a site added. Only the current revision of each entity is
 * updated.
 */
function az_icons_post_update_rename_icon_colors(&$sandbox) {
  $entity_type_manager = \Drupal::entityTypeManager();

  if (!isset($sandbox['items'])) {
    // Find the columns that hold formatted text, keyed by entity type ID and
    // field name. A column holds formatted text if it has a text format
    // column beside it: format for the value and summary of a text field, or
    // body_format for the body of a card or accordion field.
    $sandbox['columns'] = [];
    $entity_field_manager = \Drupal::service('entity_field.manager');
    foreach ($entity_type_manager->getDefinitions() as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface || !is_subclass_of($entity_type->getStorageClass(), SqlEntityStorageInterface::class)) {
        continue;
      }
      foreach ($entity_field_manager->getFieldStorageDefinitions($entity_type_id) as $field_name => $storage_definition) {
        if ($storage_definition->hasCustomStorage()) {
          continue;
        }
        $columns = array_keys($storage_definition->getColumns());
        foreach ($columns as $column) {
          $format_column = in_array($column, ['value', 'summary'], TRUE) ? 'format' : $column . '_format';
          if (in_array($format_column, $columns, TRUE)) {
            $sandbox['columns'][$entity_type_id][$field_name][] = $column;
          }
        }
      }
    }

    // Find the entities that have an icon in one of those columns.
    $sandbox['items'] = [];
    foreach ($sandbox['columns'] as $entity_type_id => $fields) {
      $query = $entity_type_manager->getStorage($entity_type_id)->getQuery()->accessCheck(FALSE);
      $has_icon = $query->orConditionGroup();
      foreach ($fields as $field_name => $columns) {
        foreach ($columns as $column) {
          $has_icon->condition("$field_name.$column", '<drupal-icon', 'CONTAINS');
        }
      }
      foreach ($query->condition($has_icon)->execute() as $id) {
        $sandbox['items'][] = [$entity_type_id, $id];
      }
    }
    $sandbox['max'] = count($sandbox['items']);
    $sandbox['progress'] = 0;
    $sandbox['updated_count'] = 0;
  }

  foreach (array_slice($sandbox['items'], $sandbox['progress'], 25) as [$entity_type_id, $id]) {
    $sandbox['progress']++;
    /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
    $entity = $entity_type_manager->getStorage($entity_type_id)->load($id);
    if (!$entity) {
      continue;
    }

    $needs_update = FALSE;
    foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
      $translation = $entity->getTranslation($langcode);
      foreach ($sandbox['columns'][$entity_type_id] as $field_name => $columns) {
        if (!$translation->hasField($field_name)) {
          continue;
        }
        foreach ($translation->get($field_name) as $item) {
          foreach ($columns as $column) {
            $value = $item->get($column)->getValue();
            if (!is_string($value)) {
              continue;
            }
            $updated_value = AZIconColorNames::updateMarkup($value);
            if ($updated_value !== $value) {
              $item->set($column, $updated_value);
              $needs_update = TRUE;
            }
          }
        }
      }
    }

    if ($needs_update) {
      // Save the current revision as a sync. Without this, the save would
      // change the entity's "changed" date, and content moderation would
      // make a new revision.
      $entity->setSyncing(TRUE);
      // az_person_node_presave() treats a sync save of a person as an import,
      // and makes a new revision. Tell it this save isn't an import. This is
      // a plain value on the entity, not a field, so it isn't saved. phpstan
      // treats every unknown entity property as a field, so it's told to
      // ignore the next line.
      /* @phpstan-ignore-next-line */
      $entity->az_person_skip_import_revision = TRUE;
      $entity->save();
      $sandbox['updated_count']++;
    }
  }

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  if ($sandbox['#finished'] >= 1) {
    return \Drupal::translation()->formatPlural(
      $sandbox['updated_count'],
      'Renamed the Arizona Bootstrap color names in icons in 1 entity.',
      'Renamed the Arizona Bootstrap color names in icons in @count entities.',
    );
  }
  return NULL;
}
