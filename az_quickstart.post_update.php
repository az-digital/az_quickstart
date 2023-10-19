<?php

/**
 * @file
 * Post update functions for AZ Quickstart.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Force import of core block_content view.
 *
 * Ensure legacy Quickstart block_content view is replaced with the Drupal core
 * version updated in 10.1.x.
 */
function az_quickstart_post_update_force_import_core_block_view(&$sandbox) {
  $config_to_import = 'views.view.block_content';
  $module_path = \Drupal::service('extension.list.module')->getPath('block_content');
  $config_storage = new FileStorage($module_path . '/config/optional');
  $entity_type = \Drupal::service('config.manager')->getEntityTypeIdByName($config_to_import);
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $id = $storage->getIDFromConfigName($config_to_import, $storage->getEntityType()->getConfigPrefix());
  $active = $storage->load($id);
  $config_record = $config_storage->read($config_to_import);

  try {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $entity = $storage->updateFromStorageRecord($active, $config_record);
    $entity->save();
  }
  catch (EntityStorageException $e) {
    \Drupal::logger('az_quickstart')->notice($e->getMessage());
  }
}
