<?php

namespace Drupal\config_filter_test\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * Provides a pirate filter that adds "Arrr" to the site name.
 *
 * @ConfigFilter(
 *   id = "pirate_filter",
 *   label = "More pirates! Arrr",
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
    // @phpstan-ignore-next-line Drupal state in test is acceptable.
    if ($name === 'system.pirates' && \Drupal::state()->get('config_filter_test_bluff', FALSE)) {
      $data = is_array($data) ? $data : [];
      $data['captain'] = 'n/a';
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

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    return array_merge($data, ['system.pirates']);
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    // @phpstan-ignore-next-line Drupal state in test is acceptable.
    if ($name === 'system.pirates' && \Drupal::state()->get('config_filter_test_bluff', FALSE)) {
      return TRUE;
    }

    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    if ($name == 'system.site') {
      $data['slogan'] = $data['slogan'] . ' Arrr';
    }

    return $data;
  }

}
