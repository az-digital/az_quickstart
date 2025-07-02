<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data parser annotation object.
 *
 * Plugin namespace: Plugin\migrate_plus\data_parser.
 *
 * @see \Drupal\migrate_plus\DataParserPluginBase
 * @see \Drupal\migrate_plus\DataParserPluginInterface
 * @see \Drupal\migrate_plus\DataParserPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DataParser extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;

  /**
   * The title of the plugin.
   */
  public string $title;

}
