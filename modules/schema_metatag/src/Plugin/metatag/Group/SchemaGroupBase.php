<?php

namespace Drupal\schema_metatag\Plugin\metatag\Group;

use Drupal\metatag\Plugin\metatag\Group\GroupBase;

/**
 * Schema.org groups should extend this class.
 */
abstract class SchemaGroupBase extends GroupBase {

  /**
   * Whether this is structured data.
   *
   * @var bool
   */
  protected $schemaMetatag;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->schemaMetatag = $plugin_definition['schema_metatag'];
  }

  /**
   * Returns whether this is structured data.
   */
  public function schemaMetatag() {
    return $this->schemaMetatag;
  }

}
