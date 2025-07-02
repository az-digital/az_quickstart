<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Treat an array of values as a separate / individual values.
 *
 * @code
 * process:
 *   field_authors:
 *     -
 *       plugin: explode
 *       delimiter: ', '
 *       source: authors
 *     -
 *       plugin: single_value
 *     -
 *       plugin: callback
 *       callable: custom_sort_authors
 *     -
 *       plugin: multiple_values
 * @endcode
 *
 * Assume the "authors" field contains comma separated author names.
 *
 * We split the names into multiple values and then use the "single_value"
 * plugin to treat them as a single array of author names. After that, we
 * pass the values through a custom sort. Callback multiple setting is false. To
 * convert from a single value to multiple, use the "multiple_values" plugin. It
 * will make the next plugin treat the values individually instead of an array
 * of values.
 *
 * @MigrateProcessPlugin(
 *   id = "multiple_values",
 *   handle_multiples = TRUE
 * )
 */
class MultipleValues extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple(): bool {
    return TRUE;
  }

}
