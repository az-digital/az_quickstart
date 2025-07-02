<?php

namespace Drupal\Tests\migmag\Traits;

/**
 * Trait for determining available features in core.
 */
trait CoreCompatibilityTrait {

  /**
   * Whether Aggregator module was removed from the current core version.
   *
   * @return bool
   *   Whether Aggregator module was removed from the current core version.
   */
  protected static function coreAggregatorIsMissing(): bool {
    return version_compare(\Drupal::VERSION, '9.4.0-dev', 'ge');
  }

  /**
   * Whether Claro and Olivero are the default (and default admin) themes.
   *
   * @return bool
   *   Whether Claro and Olivero are the default (and default admin) themes.
   */
  protected static function claroAndOliveroAreDefaultThemes(): bool {
    return version_compare(\Drupal::VERSION, '9.5.0-dev', 'ge');
  }

}
