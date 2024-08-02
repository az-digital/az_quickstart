<?php

namespace Drupal\az_course\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms a PeopleSoft year code into a text term name.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('peoplesoft_year')]
class PeopleSoftYear extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // PeopleSoft uses CYYT, century, year year, term code.
    // Summer II is no longer used.
    $terms = [
      '1' => 'Spring ',
      '2' => 'Summer ',
      '3' => 'Summer II ',
      '4' => 'Fall ',
      '5' => 'Winter ',
    ];

    $matches = [];
    if (preg_match('/^(\d)(\d\d)(\d)$/', $value, $matches)) {
      // Start with century.
      $year = 1800 + ((int) $matches[1] * 100);
      // Years.
      $year += (int) $matches[2];
      $code = (string) $matches[3];
      $year = (string) $year;
      // Term code.
      if (!empty($terms[$code])) {
        $year = $terms[$code] . $year;
      }
      $value = $year;
    }

    return $value;
  }

}
