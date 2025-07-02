<?php

namespace Drupal\config_filter\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\config_filter\Config\TransparentStorageFilterTrait;

/**
 * Base class for Config filter plugin plugins.
 */
abstract class ConfigFilterBase extends PluginBase implements ConfigFilterInterface {

  use TransparentStorageFilterTrait;

}
