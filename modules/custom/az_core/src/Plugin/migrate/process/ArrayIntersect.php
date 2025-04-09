<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Enables use of array_intersect within a migration.
 *
 * @See https://git.drupalcode.org/project/migrate_process_array/-/blob/8.x-1.x/src/Plugin/migrate/process/ArrayIntersect.php
 *
 * @code
 * process:
 *   field_of_array_values:
 *    plugin: array_intersect
 *    source: some_array_field
 *    match:
 *      - values
 *      - to
 *      - match
 * @endcode
 */
#[MigrateProcess('array_intersect')]
class ArrayIntersect extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): ?string {
    // Only process non-empty values.
    if (empty($value)) {
      return NULL;
    }

    // The input must be an array.
    if (!is_array($value)) {
      $value = [$value];
    }

    // As well as the array to match against.
    $match = $this->configuration['match'];
    if (!is_array($match)) {
      $match = [$match];
    }

    // Get the method.
    $method = empty($this->configuration['method']) ? '' : $this->configuration['method'];

    // Return results by method.
    $out = [];
    if ($method === 'assoc') {
      $array_intersect = array_intersect_assoc($value, $match);
      if (isset($array_intersect[0])) {
        $out = $array_intersect[0];
      }
    }
    elseif ($method === 'key') {
      $array_intersect = array_intersect_key($value, $match);
      if (isset($array_intersect[0])) {
        $out = $array_intersect[0];
      }
    }
    else {
      $array_intersect = array_intersect($value, $match);
      if (isset($array_intersect[0])) {
        $out = $array_intersect[0];
      }
    }

    // Migrate treats NULL as empty not not empty arrays.
    if (empty($out)) {
      return NULL;
    }

    return $out;
  }

}
