<?php

declare(strict_types=1);

namespace Drupal\file_mdm\Plugin\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines a Plugin annotation object for FileMetadata plugins.
 *
 * @Annotation
 */
class FileMetadata extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The title of the plugin.
   *
   * The string should be wrapped in a @Translation().
   *
   * @ingroup plugin_translatable
   */
  public Translation $title;

  /**
   * An informative description of the plugin.
   *
   * The string should be wrapped in a @Translation().
   *
   * @ingroup plugin_translatable
   */
  public Translation $help;

}
