<?php

namespace Drupal\az_course\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns a link to a user, or a non-link.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('instructor_link')]
class InstructorLink extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $nolink = ['uri' => 'route:<nolink>', 'title' => $value];

    // Placeholder. Check for presence of instructor when field is available
    // to create a link to faculty pages.
    if ($value === 'netid-not-found') {
      $nolink['title'] = 'unassigned';
    }

    return $nolink;
  }

}
