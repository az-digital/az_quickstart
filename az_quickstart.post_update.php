<?php

/**
 * @file
 * Post update functions for AZ Quickstart.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\user\Entity\Role;

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

/**
 * Remove unneeded block_content permissions (core + contrib) from AZQS roles.
 */
function az_quickstart_post_update_remove_block_content_permissions_from_roles(&$sandbox) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'user_role', function (Role $role) {
    $update = FALSE;
    if (in_array($role->get('id'), ['az_content_admin', 'az_content_editor'])) {
      $permissions_to_remove = [
        'administer block content types',
        'administer block types',
        'update any az_custom_menu_block block content',
        'update any az_flexible_block block content',
        'update any az_quick_links block content',
        'view restricted block content',
      ];
      foreach ($permissions_to_remove as $permission) {
        if ($role->hasPermission($permission)) {
          $role->revokePermission($permission);
          $update = TRUE;
        }
      }
    }
    return $update;
  });
}

/**
 * Enables AZ Navbar.
 */
function az_quickstart_post_update_enable_az_navbar(&$sandbox) {
  $updated = FALSE;
  $config_storage = \Drupal::service('config.storage');
  $config_factory = \Drupal::configFactory();

  if ($config_storage->exists('az_barrio.settings')) {
    $theme_settings = $config_factory->getEditable('az_barrio.settings');
    if ($theme_settings->get('az_navbar') !== TRUE) {
      $theme_settings->set('az_navbar', TRUE)->save();
      $updated = TRUE;
    }
  }

  if ($updated) {
    \Drupal::logger('az_quickstart')->notice('Enabled AZ Navbar during post update.');
  }
}
