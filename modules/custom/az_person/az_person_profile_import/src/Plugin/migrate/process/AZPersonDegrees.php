<?php

namespace Drupal\az_person_profiles_import\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns an ordered list of degrees from the profiles API.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "az_person_degrees",
  handle_multiples: TRUE,
)]

class AZPersonDegrees extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      return [];
    }
    // Sort degrees descending by year.
    usort($value, function ($a, $b) {
      $a_year = $a['CONFERRED_DEGREE']['year'] ?? '';
      $b_year = $b['CONFERRED_DEGREE']['year'] ?? '';
      return strcmp($b_year, $a_year);
    });

    $degrees = [];
    foreach ($value as $degree) {
      $d = [];
      $title = $degree['properties']['degree_title'] ?? '';
      $discipline = $degree['properties']['degree_title'] ?? '';
      $institution = $degree['institution'][0]['name'] ?? '';
      // Don't add the discipline if it's already in the degree.
      if (str_contains($title, $discipline)) {
        $discipline = '';
      }
      $year = $degree['CONFERRED_DEGREE']['year'] ?? '';
      // Compose the list of information.
      $d = [$title, $discipline, $institution, $year];
      // Remove ones that were not present.
      $d = array_filter($d);
      $degrees[] = ['value' => implode(', ', $d)];
    }

    return $degrees;
  }

}
