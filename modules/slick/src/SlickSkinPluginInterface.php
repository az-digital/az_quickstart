<?php

namespace Drupal\slick;

// @todo use Drupal\blazy\Plugin\SkinPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an interface defining Slick skins.
 *
 * @todo extends SkinPluginInterface;
 */
interface SlickSkinPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Returns the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function label();

  /**
   * Returns the Slick skins.
   *
   * This can be used to register skins for the Slick. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded slicks.
   *
   * Slick skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_slick_carousel_rounded", the CSS class is:
   * slick--skin--my-module-slick-carousel-rounded
   *
   * A skin can specify CSS and JS files to include when Slick is displayed,
   * except for a thumbnail skin which accepts CSS only.
   *
   * Each skin supports a few keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - group: A string grouping the current skin: main, thumbnail, arrows, dots.
   * - dependencies: Similar to how core library dependencies constructed.
   * - provider: A module name registering the skins.
   * - options: Extra JavaScript (Slicebox, 3d carousel, etc) options merged
   *     into existing [data-slick] attribute to be consumed by custom JS.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

  /**
   * Returns the plugin arrow skins.
   *
   * @return array
   *   The plugin arrow skins.
   */
  public function arrows();

  /**
   * Returns the plugin dot skins.
   *
   * @return array
   *   The plugin dot skins.
   */
  public function dots();

}
