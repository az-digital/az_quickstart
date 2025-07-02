<?php

namespace Drupal\blazy\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Skin item annotation object.
 *
 * @Annotation
 */
class BlazySkin extends Plugin {

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
