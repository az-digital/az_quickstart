<?php

/**
 * @file
 * Post update functions for the Asset Injector module.
 */

/**
 * Update node type conditions from "node_type" to "entity_bundle:node".
 */
function asset_injector_post_update_update_node_type_conditions() {
  $updated_configs = [];
  foreach (\Drupal::configFactory()->listAll('asset_injector.') as $asset_injector_config_name) {
    $asset_injector_config = \Drupal::configFactory()->getEditable($asset_injector_config_name);

    // Load asset injector entities and swap the "node_type" plugin for the
    // "entity_bundle:node" plugin.
    if ($asset_injector_config->get('id') && $asset_injector_config->get('conditions.node_type')) {
      $conditions = [];
      foreach ($asset_injector_config->get('conditions') as $condition_id => $condition) {
        if ($condition_id === 'node_type') {
          $condition_id = 'entity_bundle:node';
          $condition['id'] = 'entity_bundle:node';
        }
        $conditions[$condition_id] = $condition;
      }
      $asset_injector_config
        ->set('conditions', $conditions)
        ->save(TRUE);
      $updated_configs[] = $asset_injector_config_name;
    }
  }
  if ($updated_configs) {
    return t('Updated the "node_type" condition IDs on %config_names', [
      '%config_names' => implode(', ', $updated_configs),
    ]);
  }

  return NULL;
}
