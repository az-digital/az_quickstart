<?php

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityStorageException;

/**
 * Force import of core block_content view updated in Drupal core 10.1.x to
 * ensure legacy Quickstart view is replaced.
 */
function az_quicsktart_post_update_force_import_core_block_view(&$sandbox) {
  $config_to_import = 'views.view.block_content';
  $module_path = \Drupal::service('extension.list.module')->getPath('block_content');
  $config_storage = new FileStorage($module_path . '/config/optional');
  $entity_type = \Drupal::service('config.manager')->getEntityTypeIdByName($config_to_import);
  $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
  $config_record = $config_storage->read($config_to_import);
  
  try {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
    $entity = $storage->createFromStorageRecord($config_record);
    $entity->save();
  }
  catch (EntityStorageException $e) {
    \Drupal::logger('az_quickstart')->notice($e->getMessage());
  }
}
