<?php

namespace Drupal\config_filter\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\config_filter\Config\StorageFilterInterface;

/**
 * Defines an interface for Config filter plugin plugins.
 */
interface ConfigFilterInterface extends PluginInspectionInterface, StorageFilterInterface {

}
