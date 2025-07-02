<?php

namespace Drupal\blazy\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an interface defining Blazy skins.
 */
interface SkinPluginInterface extends ContainerFactoryPluginInterface {

  /**
   * Returns the plugin label.
   *
   * @return string
   *   The plugin label.
   */
  public function label();

  /**
   * Returns the Blazy skins.
   *
   * This can be used to register skins for the Blazy. Skins will be
   * available when configuring the Optionset, Field formatter, or Views style,
   * or custom coded blazys.
   *
   * Blazy skins get a unique CSS class to use for styling, e.g.:
   * If your skin name is "my_module_blazy_flip", the CSS class is:
   * blazy--skin--my-module-blazy-flip
   *
   * A skin can specify CSS and JS files to include when Blazy is displayed,
   * except for a thumbnail skin which accepts CSS only.
   *
   * Each skin supports 5 keys:
   * - name: The human readable name of the skin.
   * - description: The description about the skin, for help and manage pages.
   * - css: An array of CSS files to attach.
   * - js: An array of JS files to attach, e.g.: image zoomer, reflection, etc.
   * - dependencies: Similar to how core library dependencies constructed.
   * - provider: A module name registering the skins.
   *
   * @return array
   *   The array of the main and thumbnail skins.
   */
  public function skins();

}
