<?php

declare(strict_types = 1);

namespace Drupal\az_event_trellis\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Source plugin for retrieving data via Trellis events.
 *
 * @MigrateSource(
 *   id = "az_trellis_events_api"
 * )
 */
class AZTrellisEventSource extends SourcePluginBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * @var \Drupal\az_event_trellis\TrellisHelper
   */
  protected $trellisHelper;

  /**
   * The Trellis source ids to retrieve.
   *
   * @var array
   */
  protected array $trellisIds = [];

  /**
   * Information on the source fields to be extracted from the data.
   *
   * @var array[]
   *   Array of field information keyed by field names. A 'label' subkey
   *   describes the field for migration tools; a 'path' subkey provides the
   *   source-specific path for obtaining the value.
   */
  protected $fields = [];

  /**
   * Description of the unique ID fields for this source.
   *
   * @var array[]
   *   Each array member is keyed by a field name, with a value that is an
   *   array with a single member with key 'type' and value a column type such
   *   as 'integer'.
   */
  protected $ids = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->fields = $configuration['fields'];
    $this->ids = $configuration['ids'];
    // @todo use injection for this.
    $this->trellisHelper = \Drupal::service('az_event_trellis.trellis_helper');
    $this->trellisIds = $configuration['trellis_ids'] ?? [];
    // If no arguments are supplied, fetch the list currently on the site.
    if (empty($this->trellisIds)) {
      $ids = $this->trellisHelper->getImportedEventIds();
      $ids += $this->trellisHelper->getRecurringEventIds();
      $this->trellisIds = array_unique($ids);
    }
  }

  /**
   * Initializes the iterator with the source data.
   *
   * @return \Iterator
   *   Returns an iterable object of data for this source.
   */
  protected function initializeIterator() {
    // Fetch the events via trellis API.
    $items = $this->trellisHelper->getEvents($this->trellisIds);
    $results = [];
    $fields = [];
    // Find field selectors.
    foreach ($this->fields as $field) {
      $fields[$field['name']] = $field['selector'] ?? $field['name'];
    }
    foreach ($items as $item) {
      $result = [];
      // Transform selectors into defined fields.
      foreach ($fields as $field => $selector) {
        if (isset($item[$selector])) {
          $result[$field] = $item[$selector];
        }
      }
      $results[] = $result;
    }
    // Return an iterable.
    $obj = new \ArrayObject($results);
    return $obj->getIterator();
  }

  /**
   * Return a string representing the source ids.
   *
   * @return string
   *   Comma-separated list of ids being imported.
   */
  public function __toString(): string {
    $ids = implode(', ', $this->trellisIds);
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $fields = [];
    foreach ($this->fields as $field_info) {
      $fields[$field_info['name']] = $field_info['label'] ?? $field_info['name'];
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return $this->ids;
  }

}
