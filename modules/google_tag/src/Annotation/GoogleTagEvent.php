<?php

declare(strict_types=1);

namespace Drupal\google_tag\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Google tag event plugin.
 *
 * @Annotation
 */
final class GoogleTagEvent extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The event name.
   *
   * @var string
   */
  public $event_name;

  /**
   * The plugin label.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the event.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * A module dependency, if any.
   *
   * @var string
   *
   * @todo make string[]
   */
  public $dependency;


  /**
   * An array of context definitions describing the context used by the plugin.
   *
   * The array is keyed by context names.
   *
   * @var \Drupal\Core\Annotation\ContextDefinition[]
   */
  public $context_definitions = [];

}
