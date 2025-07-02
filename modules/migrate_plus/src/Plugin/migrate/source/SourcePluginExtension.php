<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Generally-useful extensions to the core SourcePluginBase.
 */
abstract class SourcePluginExtension extends SourcePluginBase {

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
