<?php

namespace Drupal\az_demo\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts a flat array to an associative array for use with sub_process.
 *
 * @MigrateProcessPlugin(
 *   id = "az_convert_to_associative_array"
 * )
 *
 * Available configuration keys:
 * - source: A flat array of values.
 *
 * Example:
 *
 * @code
 * process:
 *   multi_value_field:
 *     -
 *       plugin: az_convert_to_associative_array
 *       source: flat_array
 *     -
 *       plugin: sub_process
 *       process:
 *         target_id: key
 *         delta: delta
 * @endcode
 */
class ConvertToAssociativeArray extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $new_array = [];

    if (!isset($value) || !is_array($value)) {
      return $new_array;
    }

    $delta_value = 0;
    foreach ($value as $array_element) {
      $new_array[] = ['key' => $array_element, 'delta' => $delta_value];
      $delta_value++;
    }

    return $new_array;
  }

}
