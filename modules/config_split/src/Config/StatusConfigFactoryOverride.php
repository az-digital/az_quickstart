<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\State\StateInterface;

/**
 * A config factory override for config split based on the drupal state.
 *
 * @see \Drupal\config_split\Config\StatusOverride
 */
class StatusConfigFactoryOverride implements ConfigFactoryOverrideInterface {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The service constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    foreach ($this->state->get('config_split_override_state', []) as $name => $status) {
      $name = 'config_split.config_split.' . $name;
      if (in_array($name, $names)) {
        $overrides = $overrides + [$name => ['status' => (bool) $status]];
      }
    }

    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'config_split_state';
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
