<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Remove nodes / attributes of a node from a DOMDocument object.
 *
 * Configuration:
 * - selector: An XPath selector.
 * - limit: (optional) The maximum number of nodes / attributes to remove.
 * - mode: (optional) What to remove. Possible values:
 *   - element: An element (default option).
 *   - attribute: An element's attribute.
 * - attribute: An attribute name (required if mode is attribute)
 *
 * Examples:
 *
 * @code
 * process:
 *   bar:
 *     -
 *       plugin: dom
 *       method: import
 *       source: text_field
 *     -
 *       plugin: dom_remove
 *       selector: //img
 *       limit: 2
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * This example will remove the first two <img> elements from the source text
 * (if there are that many). Omit 'limit: 2' to remove all <img> elements.
 *
 * @code
 * process:
 *   bar:
 *     -
 *       plugin: dom
 *       method: import
 *       source: text_field
 *     -
 *       plugin: dom_remove
 *       mode: attribute
 *       selector: //*[@style]
 *       attribute: style
 *     -
 *       plugin: dom
 *       method: export
 * @endcode
 *
 * This example will remove "style" attributes from all tags.
 *
 * @MigrateProcessPlugin(
 *   id = "dom_remove"
 * )
 */
class DomRemove extends DomProcessBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configuration['mode'] = $this->configuration['mode'] ?? 'element';
    if ($this->configuration['mode'] === 'attribute' && !isset($this->configuration['attribute'])) {
      throw new \InvalidArgumentException('The "attribute" must be set if "mode" is set to "attribute".');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): \DOMDocument {
    $this->init($value, $destination_property);
    $walking_dead = [];
    // The PHP docs for removeChild() explain that you need to do this in two
    // steps.
    foreach ($this->xpath->query($this->configuration['selector']) as $node) {
      if (isset($this->configuration['limit']) && count($walking_dead) >= $this->configuration['limit']) {
        break;
      }
      $walking_dead[] = $node;
    }
    foreach ($walking_dead as $node) {
      switch ($this->configuration['mode']) {
        case 'attribute':
          $node->removeAttribute($this->configuration['attribute']);
          break;
        case 'element':
          $node->parentNode->removeChild($node);
          break;
      }
    }

    return $this->document;
  }

}
