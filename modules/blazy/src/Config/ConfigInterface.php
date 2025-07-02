<?php

namespace Drupal\blazy\Config;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides libraries utilities.
 */
interface ConfigInterface {

  /**
   * Returns the cache service.
   *
   * @return \Drupal\Core\Cache\CacheBackendInterface
   *   The app root.
   */
  public function cache(): CacheBackendInterface;

  /**
   * Retrieves the config factory service.
   *
   * @return \Drupal\Core\Config\ConfigFactoryInterface
   *   The config factory.
   */
  public function configFactory(): ConfigFactoryInterface;

  /**
   * Retrieves the module handler service.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
   */
  public function moduleHandler(): ModuleHandlerInterface;

  /**
   * Returns the app root.
   *
   * @return string
   *   The app root.
   */
  public function root(): string;

  /**
   * Retrieves the currently active route match object.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   The currently active route match object.
   */
  public function routeMatch(): RouteMatchInterface;

  /**
   * Returns any config, or keyed by the $setting_name.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = NULL, $group = 'blazy.settings');

  /**
   * Returns any config by the $group, alternative to ugly NULL key.
   *
   * @param string $group
   *   The settings object group key.
   *
   * @return array
   *   The config values, or empty array.
   */
  public function configMultiple($group = 'blazy.settings'): array;

  /**
   * Returns cached options identified by its cache ID, normally alterable data.
   *
   * @param string $cid
   *   The cache ID, als used for the hook_alter.
   * @param array $data
   *   The given data to cache, accepting empty array to trigger hook_alter.
   * @param bool $as_options
   *   Whether to use it for select options.
   * @param array $info
   *   The optional info containing:
   *   - reset: Whether to bypass cache,
   *   - alter: key for the hook_alter, otherwise $cid.
   *   - context: additional data or contextual info for the hook_alter.
   *
   * @return array
   *   The cache data/ options.
   */
  public function getCachedData(
    $cid,
    array $data = [],
    $as_options = TRUE,
    array $info = [],
  ): array;

  /**
   * Return the cache metadata common for all blazy-related modules.
   *
   * @param array $build
   *   The build containing #settings which has cache definitions.
   *
   * @return array
   *   The cache metadata suitable for #cache property.
   */
  public function getCacheMetadata(array $build): array;

  /**
   * Returns drupalSettings for IO.
   *
   * @param array $attach
   *   The settings which determine what library to attach, empty for defaults.
   *
   * @return object
   *   The supported IO drupalSettings.
   */
  public function getIoSettings(array $attach = []): object;

  /**
   * Import a config entity, and save it into database.
   *
   * @param array $options
   *   Containing:
   *     - module, the module name where config to be imported is stored.
   *     - basename, file name without .yml extension: slick.optionset.nav, etc.
   *     - folder, whether install, or optional.
   */
  public function import(array $options): void;

  /**
   * Returns escaped options.
   *
   * @param array $options
   *   The given options.
   *
   * @return array
   *   The modified array of options suitable for select options.
   */
  public function toOptions(array $options): array;

}
