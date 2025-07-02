<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Treat an array of values as a single value.
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
 * @endcode
 *
 * Assume the "authors" field contains comma separated author names.
 *
 * After the explode, we end up with each author name as an individual value.
 * But if we want to perform a sort on all values using a callback, we will
 * need to send all the values to a callable together as an array of author
 * names. Calling the "single_value" plugin in such a case will combine all the
 * values into a single array for the next plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "single_value",
 *   handle_multiples = TRUE
 * )
 */
class SingleValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return $value;
  }

}
