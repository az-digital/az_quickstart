<?php

namespace Drupal\devel\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a DevelDumper annotation object.
 *
 * @Annotation
 *
 * @see \Drupal\devel\DevelDumperPluginManager
 * @see \Drupal\devel\DevelDumperInterface
 * @see \Drupal\devel\DevelDumperBase
 * @see plugin_api
 */
class DevelDumper extends Plugin {

  /**
   * The human-readable name of the DevelDumper type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A short description of the DevelDumper type.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

}
