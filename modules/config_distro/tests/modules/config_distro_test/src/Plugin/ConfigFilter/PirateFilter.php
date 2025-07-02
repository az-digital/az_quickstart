<?php

namespace Drupal\config_distro_test\plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * Provides a pirate filter that adds "Arrr" to the site name.
 *
 * @ConfigFilter(
 *   id = "distro_pirate_filter",
 *   label = "More pirates! Arrr",
 *   storages = {"config_distro.storage.distro"},
 *   weight = 10
 * )
 */
class PirateFilter extends ConfigFilterBase {

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    if ($name == 'system.site') {
      $data['name'] = $data['name'] . ' Arrr';
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    if (in_array('system.site', $names)) {
      $data['system.site'] = $this->filterRead('system.site', $data['system.site']);
    }

    return $data;
  }

}
