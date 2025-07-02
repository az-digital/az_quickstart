<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an authentication annotation object.
 *
 * Plugin namespace: Plugin\migrate_plus\authentication.
 *
 * @see \Drupal\migrate_plus\AuthenticationPluginBase
 * @see \Drupal\migrate_plus\AuthenticationPluginInterface
 * @see \Drupal\migrate_plus\AuthenticationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Authentication extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The title of the plugin.
   */
  public string $title;

}
