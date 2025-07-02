<?php

namespace Drupal\config_distro\Plugin;

use Drupal\config_filter\Plugin\ConfigFilterBase;

/**
 * Base class for Config Distro filter plugin plugins.
 *
 * This is the base class modules providing config filter plugins for the
 * distro storage can use. It is an extension of the plugin base class to
 * maintain backwards compatibility in case we need to add methods for the
 * filters for config distro. This is not mandatory but recommended for the
 * additional features to work.
 */
abstract class ConfigDistroFilterBase extends ConfigFilterBase implements ConfigDistroFilterInterface {

}
