<?php

namespace Drupal\crop\Events;

use Drupal\Component\EventDispatcher\Event;

/**
 * Collects "Automatic crop" providers.
 */
class AutomaticCropProviders extends Event {

  /**
   * Automatic Crop provider list.
   *
   * @var array
   */
  protected $providers = [];

  /**
   * Adds provider.
   *
   * @param array $provider
   *   Register provider to providers list.
   */
  public function registerProvider(array $provider) {
    $this->providers[key($provider)] = current($provider);
  }

  /**
   * Sets automatic crop providers.
   *
   * @param array $providers
   *   List of automatic crop providers.
   */
  public function setProviders(array $providers) {
    $this->providers = $providers;
  }

  /**
   * Gets automatic crop providers.
   *
   * @return array
   *   List of providers.
   */
  public function getProviders() {
    return $this->providers;
  }

}
