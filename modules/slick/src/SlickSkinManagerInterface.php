<?php

namespace Drupal\slick;

// @todo use Drupal\blazy\Skin\SkinManagerBaseInterface;
/**
 * Provides an interface defining Slick skins, and asset managements.
 *
 * @todo extends SkinManagerBaseInterface
 */
interface SlickSkinManagerInterface {

  /**
   * Returns cache backend service.
   */
  public function getCache();

  /**
   * Returns app root.
   */
  public function root();

  /**
   * Provides slick skins and libraries.
   *
   * @param array $load
   *   The loaded libraries being modified.
   * @param array $attach
   *   The settings which determine what library to attach.
   * @param object $blazies
   *   The settings.blazies object for convenient, optional for BC.
   */
  public function attach(array &$load, array $attach, $blazies = NULL): void;

  /**
   * Provides core libraries.
   *
   * @param array $load
   *   The loaded libraries being modified.
   * @param array $attach
   *   The settings which determine what library to attach.
   * @param object $blazies
   *   The settings.blazies object for convenient, optional for BC.
   */
  public function attachSkin(array &$load, array $attach, $blazies = NULL): void;

  /**
   * Returns slick config shortcut.
   *
   * @param string $key
   *   The setting key.
   * @param string $group
   *   The settings object group key.
   *
   * @return mixed
   *   The config value(s), or empty.
   */
  public function config($key = '', $group = 'slick.settings');

  /**
   * Returns the supported skins.
   */
  public function getConstantSkins(): array;

  /**
   * Returns easing library path if available, else FALSE.
   */
  public function getEasingPath(): ?string;

  /**
   * Returns an instance of a plugin by given plugin id.
   *
   * @param string $id
   *   The plugin id.
   *
   * @return \Drupal\slick\SlickSkinPluginInterface
   *   Return instance of SlickSkin.
   */
  public function load($id): SlickSkinPluginInterface;

  /**
   * Returns plugin instances.
   */
  public function loadMultiple(): array;

  /**
   * Returns slick skins registered via SlickSkin plugin and or defaults.
   */
  public function getSkins(): array;

  /**
   * Returns available slick skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array;

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild(): array;

  /**
   * Returns slick library path if available, else FALSE.
   */
  public function getSlickPath(): ?string;

  /**
   * Implements hook_library_info_alter().
   */
  public function libraryInfoAlter(&$libraries, $extension): void;

  /**
   * Check for breaking libraries: Slick 1.9.0, or Accessible Slick.
   */
  public function isBreaking(): bool;

}
