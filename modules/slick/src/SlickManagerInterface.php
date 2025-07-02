<?php

namespace Drupal\slick;

use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\blazy\BlazyManagerBaseInterface;
use Drupal\slick\Entity\Slick;

/**
 * Defines re-usable services and functions for slick plugins.
 *
 * @todo remove BlazyManagerBaseInterface when phpstand sniffs inheritance.
 */
interface SlickManagerInterface extends BlazyManagerBaseInterface, TrustedCallbackInterface {

  /**
   * Returns slick skin manager service.
   */
  public function skinManager(): SlickSkinManagerInterface;

  /**
   * Provides a shortcut to attach skins only if required.
   */
  public function attachSkin(array &$load, array $attach, $blazies = NULL): void;

  /**
   * Returns a renderable array of both main and thumbnail slick instances.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of slick contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *   - thumb: An associative array of slick thumbnail following the same
   *     structure as the main display: $build['thumb']['items'], etc.
   *
   * @return array
   *   The renderable array of both main and thumbnail slick instances.
   */
  public function build(array $build): array;

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items, array &$settings): array;

  /**
   * Returns slick skins registered via SlickSkin plugin, or defaults.
   */
  public function getSkins(): array;

  /**
   * Returns available slick skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array;

  /**
   * Load the optionset with a fallback.
   *
   * @param string $name
   *   The optionset name.
   *
   * @return \Drupal\slick\Entity\Slick
   *   The optionset object.
   */
  public function loadSafely($name): Slick;

  /**
   * Builds the Slick instance as a structured array ready for ::renderer().
   */
  public function preRenderSlick(array $element): array;

  /**
   * One slick_theme() to serve multiple displays: main, overlay, thumbnail.
   */
  public function preRenderSlickWrapper($element): array;

}
