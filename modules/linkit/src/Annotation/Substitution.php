<?php

namespace Drupal\linkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a substitution annotation object.
 *
 * @Annotation
 */
class Substitution extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the substitution.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
