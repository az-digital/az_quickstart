<?php

/**
 * @file
 * Post update functions for AZ Quickstart.
 */

use Drupal\Component\Utility\Crypt;
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
  $entity_type_id = \Drupal::service('config.manager')->getEntityTypeIdByName($config_to_import);
  /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
  /** @var \Drupal\Core\Config\Entity\ConfigEntityTypeInterface $entity_type */
  $entity_type = $storage->getEntityType();
  $id = $storage->getIDFromConfigName($config_to_import, $entity_type->getConfigPrefix());
  $active = $storage->load($id);
  $config_record = $config_storage->read($config_to_import);

  // Add a config hash if necessary.
  if (empty($config_record['_core']['default_config_hash'])) {
    $config_record['_core']['default_config_hash'] = Crypt::hashBase64(serialize($config_record));
  }

  try {
    $entity = $storage->updateFromStorageRecord($active, $config_record);
    $entity->save();
  }
  catch (EntityStorageException $e) {
    \Drupal::logger('az_quickstart')->notice($e->getMessage());
  }
}
