<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts a flat array to a nested array for use with sub_process.
 *
 * @MigrateProcessPlugin(
 *   id = "az_prepare_array_for_sub_process"
 * )
 *
 * Available configuration keys:
 * - source: A flat array of values.
 *
 * This plugin returns an array of associative arrays which have these
 * key-value pairs:
 * - "value" => (source array value)
 * - "delta" => (incrementing index value)
 *
 * Example:
 *
 * @code
 * process:
 *   multi_value_field:
 *     -
 *       plugin: az_prepare_array_for_sub_process
 *       source: flat_array
 *     -
 *       plugin: sub_process
 *       process:
 *         target_id: value
 *         delta: delta
 * @endcode
 */
class PrepareArrayForSubProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $return_array = [];
    if (!isset($value) || !is_array($value)) {
      return $return_array;
    }

    $delta_value = 0;
    foreach ($value as $array_element) {
      $return_array[] = ['value' => $array_element, 'delta' => $delta_value];
      $delta_value++;
    }

    return $return_array;
  }

}
