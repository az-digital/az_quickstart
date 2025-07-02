<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a data fetcher annotation object.
 *
 * Plugin namespace: Plugin\migrate_plus\data_fetcher.
 *
 * @see \Drupal\migrate_plus\DataFetcherPluginBase
 * @see \Drupal\migrate_plus\DataFetcherPluginInterface
 * @see \Drupal\migrate_plus\DataFetcherPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class DataFetcher extends Plugin {

  /**
   * The plugin ID.
   */
  public string $id;

  /**
   * The title of the plugin.
   */
  public string $title;

}
