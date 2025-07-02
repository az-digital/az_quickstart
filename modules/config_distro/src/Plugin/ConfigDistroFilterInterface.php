<?php

namespace Drupal\config_distro\Plugin;

use Drupal\config_filter\Plugin\ConfigFilterInterface;

/**
 * Defines an interface for Config Distro filter plugin plugins.
 *
 * We might add additional methods to the filter in order to improve the UI
 * or allow for a specialized workflow that is outside the scope of the original
 * config filter plugin spec. Use the associated base class for backwards
 * compatibility.
 */
interface ConfigDistroFilterInterface extends ConfigFilterInterface {

}
