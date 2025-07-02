<?php

namespace Drupal\blazy\Skin;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\blazy\Plugin\SkinPluginInterface;

/**
 * Provides a base interface defining skins, and asset managements.
 */
interface SkinManagerBaseInterface extends MapperInterface {

  /**
   * Returns any config, or keyed by the $key.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = NULL, $group = NULL);

  /**
   * Returns an instance of a plugin by given plugin id.
   *
   * @param string $id
   *   The plugin id.
   *
   * @return \Drupal\blazy\Plugin\SkinPluginInterface
   *   Return instance of BlazySkin.
   */
  public function load($id): SkinPluginInterface;

  /**
   * Returns all plugins.
   */
  public function loadMultiple(): array;

  /**
   * Returns skins registered via BlazySkin plugin or defaults.
   */
  public function getSkins(): array;

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild(): array;

}
