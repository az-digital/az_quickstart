<?php

/**
 * @file
 * Provides view data for google_tag.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 */
function google_tag_views_data() {
  $data = [];
  if (\Drupal::moduleHandler()->moduleExists('commerce_product')) {

    foreach (['commerce_product', 'commerce_product_variation'] as $entity_type_id) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $data_table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();
      $data[$data_table]['commerce_product_view_item_list'] = [
        'title' => t('Google Tag: View item list event'),
        'help' => t('Send a view_item_list event to Google Analytics'),
        'area' => [
          'id' => 'commerce_product_view_item_list',
        ],
      ];
    }
  }
  return $data;
}
