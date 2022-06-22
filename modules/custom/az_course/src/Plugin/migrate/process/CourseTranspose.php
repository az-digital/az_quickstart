<?php

namespace Drupal\az_course\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\ProcessPluginBase;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "course_transpose"
 * )
 */
class CourseTranspose extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($table, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Make sure that $table is an array of arrays.
    if (!is_array($table) || $table === []) {
      return [];
    }
    foreach ($table as &$value) {
      $value = (array) $value;
    }

    return array_map(NULL, ...$table);
  }

}
