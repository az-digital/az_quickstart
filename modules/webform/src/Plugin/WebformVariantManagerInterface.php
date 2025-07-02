<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available webform variants.
 */
interface WebformVariantManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface, FallbackPluginManagerInterface, CategorizingPluginManagerInterface, WebformPluginManagerExcludedInterface {

}
