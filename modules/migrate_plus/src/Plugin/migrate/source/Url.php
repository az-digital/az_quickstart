<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\source;

use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "url"
 * )
 */
class Url extends SourcePluginExtension {

  /**
   * The source URLs to retrieve.
   *
   * @var array
   */
  protected array $sourceUrls = [];

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected DataParserPluginInterface $dataParserPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    if (!is_array($configuration['urls'])) {
      $configuration['urls'] = [$configuration['urls']];
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->sourceUrls = $configuration['urls'];
  }

  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString(): string {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', $this->sourceUrls);
    return $urls;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   *   The data parser plugin.
   */
  public function getDataParserPlugin(): DataParserPluginInterface {
    if (!isset($this->dataParserPlugin)) {
      $this->dataParserPlugin = \Drupal::service('plugin.manager.migrate_plus.data_parser')->createInstance($this->configuration['data_parser_plugin'], $this->configuration);
    }
    return $this->dataParserPlugin;
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator(): DataParserPluginInterface {
    return $this->getDataParserPlugin();
  }

}
