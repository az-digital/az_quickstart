<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\State\StateInterface;

/**
 * A service for config override for config split based on the drupal state.
 *
 * @see \Drupal\config_split\Config\StatusConfigFactoryOverride
 */
class StatusOverride {

  /**
   * The drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The cache invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheInvalidator;

  /**
   * The service constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The drupal state.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheInvalidator
   *   The cache invalidator.
   */
  public function __construct(StateInterface $state, CacheTagsInvalidatorInterface $cacheInvalidator) {
    $this->state = $state;
    $this->cacheInvalidator = $cacheInvalidator;
  }

  /**
   * Set a config split state locally.
   *
   * @param string $name
   *   The name of the config split.
   * @param bool|null $active
   *   The state, null to reset.
   */
  public function setSplitOverride(string $name, ?bool $active = NULL) {
    $name = self::fixName($name);
    $overrides = $this->state->get('config_split_override_state', []);
    if ($active === NULL) {
      unset($overrides[$name]);
    }
    else {
      $overrides[$name] = $active;
    }
    $this->state->set('config_split_override_state', $overrides);
    $this->cacheInvalidator->invalidateTags(['config:config_split.config_split.' . $name]);
  }

  /**
   * Get the split override setting.
   *
   * @param string $name
   *   The name of the split.
   *
   * @return bool|null
   *   The state, null if not managed.
   */
  public function getSplitOverride(string $name) {
    $name = self::fixName($name);
    $overrides = $this->state->get('config_split_override_state', []);
    if (isset($overrides[$name])) {
      return (bool) $overrides[$name];
    }
    return NULL;
  }

  /**
   * Check settings.php for overrides.
   *
   * @param string $name
   *   The name of the split.
   *
   * @return bool|null
   *   The overridden status from settings.php
   */
  public function getSettingsOverride(string $name) {
    $name = 'config_split.config_split.' . self::fixName($name);
    // This assumes that the config is overwritten as recommended.
    if (isset($GLOBALS['config'][$name], $GLOBALS['config'][$name]['status'])) {
      return (bool) $GLOBALS['config'][$name]['status'];
    }
    return NULL;
  }

  /**
   * Make sure the split name is just the machine name.
   *
   * @param string $name
   *   The split name.
   *
   * @return string
   *   The split name.
   */
  private static function fixName(string $name): string {
    if (strpos($name, 'config_split.config_split.') === 0) {
      return substr($name, strlen('config_split.config_split.'));
    }
    return $name;
  }

}
