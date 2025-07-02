<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base data fetcher implementation.
 *
 * @see \Drupal\migrate_plus\Annotation\DataFetcher
 * @see \Drupal\migrate_plus\DataFetcherPluginInterface
 * @see \Drupal\migrate_plus\DataFetcherPluginManager
 * @see plugin_api
 */
abstract class DataFetcherPluginBase extends PluginBase implements DataFetcherPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getNextUrls(string $url): array {
    return [];
  }

}
