<?php

namespace Drupal\slick\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SlickSkin item annotation object.
 *
 * @Annotation
 */
class SlickSkin extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
