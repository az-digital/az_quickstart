<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base data parser implementation.
 *
 * @see \Drupal\migrate_plus\Annotation\DataParser
 * @see \Drupal\migrate_plus\DataParserPluginInterface
 * @see \Drupal\migrate_plus\DataParserPluginManager
 * @see plugin_api
 */
abstract class DataParserPluginBase extends PluginBase implements DataParserPluginInterface {

  /**
   * List of source urls.
   *
   * @var string[]
   */
  protected ?array $urls;

  /**
   * Index of the currently-open url.
   */
  protected ?int $activeUrl = NULL;

  /**
   * String indicating how to select an item's data from the source.
   *
   * @var string|int
   */
  protected $itemSelector;

  /**
   * Current item when iterating.
   *
   * @var mixed
   */
  protected $currentItem = NULL;

  /**
   * Value of the ID for the current item when iterating.
   */
  protected ?array $currentId = NULL;

  /**
   * The data retrieval client.
   */
  protected DataFetcherPluginInterface $dataFetcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->urls = $configuration['urls'];
    $this->itemSelector = $configuration['item_selector'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Returns the initialized data fetcher plugin.
   */
  public function getDataFetcherPlugin(): DataFetcherPluginInterface {
    if (!isset($this->dataFetcher)) {
      $this->dataFetcher = \Drupal::service('plugin.manager.migrate_plus.data_fetcher')->createInstance($this->configuration['data_fetcher_plugin'], $this->configuration);
    }
    return $this->dataFetcher;
  }

  /**
   * {@inheritdoc}
   */
  public function rewind(): void {
    $this->activeUrl = NULL;
    $this->next();
  }

  /**
   * {@inheritdoc}
   */
  public function next(): void {
    $this->currentItem = $this->currentId = NULL;
    if (is_null($this->activeUrl)) {
      if (!$this->nextSource()) {
        // No data to import.
        return;
      }
    }
    // At this point, we have a valid open source url, try to fetch a row from
    // it.
    $this->fetchNextRow();
    // If there was no valid row there, try the next url (if any).
    if (is_null($this->currentItem)) {
      while ($this->nextSource()) {
        $this->fetchNextRow();
        if ($this->valid()) {
          break;
        }
      }
    }
    if ($this->valid()) {
      foreach ($this->configuration['ids'] as $id_field_name => $id_info) {
        $this->currentId[$id_field_name] = $this->currentItem[$id_field_name];
      }
    }
  }

  /**
   * Opens the specified URL.
   *
   * @param string $url
   *   URL to open.
   */
  abstract protected function openSourceUrl(string $url): bool;

  /**
   * Retrieves the next row of data. populating currentItem.
   */
  abstract protected function fetchNextRow(): void;

  /**
   * Advances the data parser to the next source url.
   */
  protected function nextSource(): bool {
    if (empty($this->urls)) {
      return FALSE;
    }

    while ($this->activeUrl === NULL || (count($this->urls) - 1) > $this->activeUrl) {
      if (is_null($this->activeUrl)) {
        $this->activeUrl = 0;
      }
      else {
        // Increment the activeUrl so we try to load the next source.
        ++$this->activeUrl;
        if ($this->activeUrl >= count($this->urls)) {
          return FALSE;
        }
      }

      if ($this->openSourceUrl($this->urls[$this->activeUrl])) {
        if (!empty($this->configuration['pager'])) {
          $this->addNextUrls($this->activeUrl);
        }
        // We have a valid source.
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Add next page of source data following the active URL.
   *
   * @param int $activeUrl
   *   The index within the source URL array to insert the next URL resource.
   *   This is parameterized to enable custom plugins to control the ordering of
   *   next URLs injected into the source URL backlog.
   */
  protected function addNextUrls(int $activeUrl = 0): void {
    $next_urls = $this->getNextUrls($this->urls[$this->activeUrl]);

    if (!empty($next_urls)) {
      array_splice($this->urls, $activeUrl + 1, 0, $next_urls);
      $this->urls = array_values(array_unique($this->urls));
    }
  }

  /**
   * Collected the next urls from a paged response.
   *
   * @param string $url
   *   URL of the currently active source.
   *
   * @return array
   *   Array of URLs representing next paged resources.
   */
  protected function getNextUrls(string $url): array {
    return $this->getDataFetcherPlugin()->getNextUrls($url);
  }

  /**
   * {@inheritdoc}
   */
  public function current(): mixed {
    return $this->currentItem;
  }

  /**
   * {@inheritdoc}
   */
  public function currentUrl(): ?string {
    $index = $this->activeUrl ?: \array_key_first($this->urls);

    return $this->urls[$index] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function key(): ?array {
    return $this->currentId;
  }

  /**
   * {@inheritdoc}
   */
  public function valid(): bool {
    return !empty($this->currentItem);
  }

  /**
   * {@inheritdoc}
   */
  public function count(): int {
    return iterator_count($this);
  }

  /**
   * Return the selectors used to populate each configured field.
   *
   * @return string[]
   *   Array of selectors, keyed by field name.
   */
  protected function fieldSelectors(): array {
    $fields = [];
    foreach ($this->configuration['fields'] as $field_info) {
      if (isset($field_info['selector'])) {
        $fields[$field_info['name']] = $field_info['selector'];
      }
    }
    return $fields;
  }

}
