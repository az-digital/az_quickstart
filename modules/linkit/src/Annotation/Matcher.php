<?php

namespace Drupal\linkit\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a matcher annotation object.
 *
 * Plugin Namespace: Plugin\Linkit\Matcher.
 *
 * @see \Drupal\linkit\MatcherInterface
 * @see \Drupal\linkit\MatcherBase
 * @see \Drupal\linkit\MatcherManager
 * @see plugin_api
 *
 * @Annotation
 */
class Matcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the matcher.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
