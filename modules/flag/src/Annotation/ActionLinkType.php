<?php

namespace Drupal\flag\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an ActionLink annotation object.
 *
 * @Annotation
 */
class ActionLinkType extends Plugin {
  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin description.
   *
   * @var string
   */
  public $description;

}
