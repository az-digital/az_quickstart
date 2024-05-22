<?php

namespace Drupal\az_course\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Flattens the source array value and makes each value unique.
 *
 * Example:
 *
 * @code
 * process:
 *   tags:
 *      -
 *        plugin: default_value
 *        source: foo
 *        default_value: [bar, bar, [alpha, beta]]
 *      -
 *        plugin: course_squash
 * @endcode
 *
 * In this example, the default_value process returns [bar, bar, [alpha, beta]]
 * (given a NULL value of foo). At this point, Migrate would try to import three
 * items: bar, bar and [alpha, beta]. The latter is not a valid one and won't be
 * imported. We need to pass the values through the flatten processor to obtain
 * a three items array [bar, alpha, beta], suitable for import.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "course_squash",
 *   handle_multiples = TRUE
 * )
 */
class CourseSquash extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // In the case of single value xpath we add to array for consistency.
    if (!is_array($value)) {
      $value = [$value];
    }
    return array_unique(iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($value)), FALSE));
  }

}
