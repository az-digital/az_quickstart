<?php

declare(strict_types=1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Builds an array based on configuration, source, destination, and pipeline.
 *
 * Usage:
 *
 * @code
 * process:
 *   bar:
 *     plugin: array_template
 *     source: foo
 *     template:
 *       key: literal string
 *       properties:
 *         - source:field_body/0/value
 *         - dest:field_body/0/value
 *         - pipeline:some/nested/key
 * @endcode
 *
 * The result is an array with the same structure (string and numeric keys,
 * nesting) as the template. Any string value starting with 'source:' or 'dest:'
 * is replaced by the corresponding source or destination property. Do not
 * prefix destination properties with '@'. The string value 'pipeline:' is
 * replaced with the source, or the previous value from the process pipeline.
 * You can also extract keys using the '/' separator.
 *
 * For example, to convert an indexed array to a keyed array,
 *
 * @code
 * process:
 *   field_paragraph:
 *     - plugin: migration_lookup
 *       # ...
 *     - plugin: array_template
 *       template:
 *         target_id: pipeline:0
 *         target_revision_id: pipeline:1
 * @endcode
 *
 * If you want a literal string like 'source:foo' in the result, then a
 * work-around is to define a constant in the source configuration:
 *
 * @code
 * source:
 *   # ...
 *   constants:
 *     do_not_process_me: source:foo
 *   process:
 *     some_field:
 *       - plugin: array_template
 *         template:
 *           - source:constants/do_not_process_me
 * @endcode
 *
 * @MigrateProcessPlugin(id = "array_template")
 */
final class ArrayTemplate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if (!is_array($configuration['template'] ?? NULL)) {
      throw new \InvalidArgumentException('The "template" must be set to an array.');
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): array {
    $template = $this->configuration['template'] ?? NULL;
    $args = ['row' => $row, 'pipeline' => $value];
    array_walk_recursive($template, [$this, 'process'], $args);

    return $template;
  }

  /**
   * Replaces source, destination, or pipeline with the correct value.
   *
   * @param mixed $value
   *   The array value as provided by arraywalk_recursive(): any type other than
   *   array. Passed by reference.
   * @param string $key
   *   The array key as provided by arraywalk_recursive(): ignored.
   * @param array $args
   *   An array with the keys
   *   - row: the current Row object;
   *   - pipeline: the pipeline or source value for the process.
   */
  protected function process(&$value, string $key, array $args): void {
    if (!is_string($value)) {
      return;
    }

    [$type, $key] = explode(':', "$value:", 2);
    if ($key === '') {
      return;
    }

    // Strip the added ':'.
    $key = substr($key, 0, -1);
    ['row' => $row, 'pipeline' => $pipeline] = $args;
    $value = match($type) {
      'source' => $row->getSourceProperty($key),
      'dest' => $row->getDestinationProperty($key),
      'pipeline' => $key === '' ? $pipeline : NestedArray::getValue($pipeline, explode('/', $key)),
      default => $value,
    };
  }

}
