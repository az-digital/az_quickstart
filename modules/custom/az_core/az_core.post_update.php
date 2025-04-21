<?php

/**
 * @file
 * Post-update functions for AZ Core module.
 */

use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;

/**
 * Add missing UUIDs to existing Quickstart override config entities.
 */
function az_core_post_update_add_missing_config_uuids() {
  // Get all QuicsktartConfigProvider config data.
  $override_config_data = [];
  $providers = \Drupal::service('config_provider.collector')->getConfigProviders();
  foreach ($providers as $provider) {
    if ($provider instanceof QuickstartConfigProvider) {
      $extension_lister = \Drupal::service('extension.list.module');
      $installed = array_intersect_key($extension_lister->getList(), $extension_lister->getAllInstalledInfo());
      $override_config_data = $provider->getOnlyOverrideConfig($installed);
    }
  }

  // Generate UUID if one doesn't exist (for config entities only).
  foreach (array_keys($override_config_data) as $name) {
    if (\Drupal::service('config.manager')->getEntityTypeIdByName($name)) {
      $config = \Drupal::service('config.factory')->getEditable($name);
      if ($config->get('uuid') === NULL) {
        $config->set('uuid', \Drupal::service('uuid')->generate());
        $config->save();
        // \Drupal::service('config.storage')->write($name, $config->getRawData());
        \Drupal::logger('az_core')->notice("Added missing UUID to @config_id.", [
          '@config_id' => $name,
        ]);
      }
    }
  }
}
