<?php

/**
 * @file
 * Post update functions for az_icons module.
 */

use Drupal\az_icons\Utility\AZIconColorNames;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Query\QueryException;
use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Rename the Arizona Bootstrap color names saved in icons.
 *
 * Arizona Bootstrap 5.3.0 renamed its brand colors (for example, Sky is now
 * Rain). An icon saves its color in formatted text, for example
 * <drupal-icon data-icon-id="material_symbols:home"
 * data-icon-settings='{"color":"sky"}'>. This renames the color of every
 * arizona_icons and material_symbols icon, in every formatted text field,
 * including fields a site added. Some paragraphs are skipped; see
 * _az_icons_used_paragraph_ids().
 *
 * AZContentFieldUpdater saves each change as a new revision, with a log
 * message that starts with the name of this function.
 */
function az_icons_post_update_rename_icon_colors(&$sandbox) {
  $entity_type_manager = \Drupal::entityTypeManager();

  if (!isset($sandbox['fields'])) {
    // Find the columns that hold formatted text, and the entities that have
    // an icon in one.
    $sandbox['formats'] = AZIconColorNames::getIconFormatIds();
    $sandbox['fields'] = [];
    $sandbox['max'] = 0;
    $entity_field_manager = \Drupal::service('entity_field.manager');
    foreach ($sandbox['formats'] ? $entity_type_manager->getDefinitions() : [] as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface || !is_subclass_of($entity_type->getStorageClass(), SqlEntityStorageInterface::class)) {
        continue;
      }
      foreach ($entity_field_manager->getFieldStorageDefinitions($entity_type_id) as $field_name => $storage_definition) {
        if ($storage_definition->hasCustomStorage()) {
          continue;
        }
        foreach (AZIconColorNames::getTextColumns($storage_definition) as $column => $format_column) {
          $ids = $entity_type_manager->getStorage($entity_type_id)->getQuery()
            ->accessCheck(FALSE)
            ->condition("$field_name.$format_column", $sandbox['formats'], 'IN')
            ->condition("$field_name.$column", '<drupal-icon', 'CONTAINS')
            ->execute();
          if ($entity_type_id === 'paragraph') {
            $ids = _az_icons_used_paragraph_ids($ids);
          }
          if ($ids) {
            $sandbox['fields'][] = [
              'entity_type_id' => $entity_type_id,
              'field_name' => $field_name,
              'value_key' => $column,
              'format_key' => $format_column,
              // Users have no revisions. Without this, a user with an icon in
              // a text field would make AZContentFieldUpdater throw an
              // exception, and the update would stop.
              'create_revisions' => $entity_type->isRevisionable(),
              'sandbox' => ['ids' => array_values($ids), 'max' => count($ids)],
            ];
            $sandbox['max'] += count($ids);
          }
        }
      }
    }
    $sandbox['current'] = 0;
    $sandbox['progress'] = 0;
    $sandbox['updated_count'] = 0;
  }

  // Give the current column to AZContentFieldUpdater, 25 entities per call.
  // Move on to the next column when that column is done.
  if (isset($sandbox['fields'][$sandbox['current']])) {
    $field = &$sandbox['fields'][$sandbox['current']];
    $progress = $field['sandbox']['progress'] ?? 0;
    \Drupal::service('az_core.content_field_updater')->processFieldUpdates(
      $field['entity_type_id'],
      $field['field_name'],
      AZIconColorNames::updateMarkup(...),
      $field['sandbox'],
      [
        'create_revisions' => $field['create_revisions'],
        'prefix' => __FUNCTION__,
        'suffix' => 'for Arizona Bootstrap 5.3 color names',
        'batch_size' => 25,
        'value_key' => $field['value_key'],
        'format_key' => $field['format_key'],
        'format_required' => TRUE,
        'allowed_formats' => $sandbox['formats'],
      ],
    );
    $sandbox['progress'] += $field['sandbox']['progress'] - $progress;
    if ($field['sandbox']['#finished'] >= 1) {
      $sandbox['updated_count'] += $field['sandbox']['updated_count'];
      $sandbox['current']++;
    }
    unset($field);
  }

  if (isset($sandbox['fields'][$sandbox['current']])) {
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
    return NULL;
  }
  $sandbox['#finished'] = 1;
  // AZContentFieldUpdater counts each column, in each language, that it
  // changed. Each one is a new revision. For example, a page with icons in its
  // body and in its summary counts as 2.
  return \Drupal::translation()->formatPlural(
    $sandbox['updated_count'],
    'Renamed the Arizona Bootstrap color names in icons in 1 field value.',
    'Renamed the Arizona Bootstrap color names in icons in @count field values.',
  );
}

/**
 * Returns the paragraphs that are safe to give AZContentFieldUpdater.
 *
 * AZContentFieldUpdater loads the current revision of each paragraph, saves
 * the renamed copy, and makes a new revision of the parent that shows it.
 * This leaves out:
 * - A paragraph that an editor removed from a page. It is still saved,
 *   because old revisions of the page use it. Without this check, the page
 *   would get a revision for a paragraph it doesn't show.
 * - A paragraph whose current revision is a draft's copy. Reverting a page to
 *   a draft revision from the Revisions tab does this. Without this check,
 *   the update would put the draft's unfinished edits on the live page.
 * - A paragraph on content without revisions, such as a user. Without this
 *   check, AZContentFieldUpdater would throw an exception, and the update
 *   would stop.
 *
 * @param array $ids
 *   The paragraph IDs.
 *
 * @return array
 *   The IDs of the paragraphs in $ids that are safe to update.
 */
function _az_icons_used_paragraph_ids(array $ids): array {
  $entity_type_manager = \Drupal::entityTypeManager();
  $used_ids = [];
  foreach (array_chunk($ids, 50) as $chunk) {
    foreach ($entity_type_manager->getStorage('paragraph')->loadMultiple($chunk) as $paragraph) {
      $parent_type = $paragraph->get('parent_type')->value;
      $parent_field = $paragraph->get('parent_field_name')->value;
      if (empty($parent_type) || empty($parent_field)) {
        continue;
      }
      // Content without revisions, such as a user.
      $parent_definition = $entity_type_manager->getDefinition($parent_type, FALSE);
      if (!$parent_definition || !$parent_definition->isRevisionable()) {
        continue;
      }
      try {
        $count = $entity_type_manager->getStorage($parent_type)->getQuery()
          ->accessCheck(FALSE)
          ->condition("$parent_field.target_id", $paragraph->id())
          ->condition("$parent_field.target_revision_id", $paragraph->getRevisionId())
          ->count()
          ->execute();
      }
      catch (QueryException) {
        // The parent's field was removed.
        continue;
      }
      if ($count) {
        $used_ids[] = $paragraph->id();
      }
    }
  }
  return $used_ids;
}
