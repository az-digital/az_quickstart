<?php

namespace Drupal\az_person_eds_import\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Skips processing the current row for data privacy requirements.
 *
 * Available configuration keys:
 * - attribute: Attribute name to check for opt-out values
 * - privacy: The value to check for inside the attribute
 *   stops processing if encountered.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: 'az_eds_privacy',
  handle_multiples: TRUE,
)]
class EDSPrivacy extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // @todo add configuration for student-only requirements.
    // (must compare to object class, optionally)
    // Determine which portions of the row our filter uses.
    $optout = $this->configuration['privacy'];
    $attribute = $this->configuration['attribute'];
    $attribute = $row->get($attribute) ?? [];
    if (!is_array($attribute)) {
      $attribute = [$attribute];
    }

    // If our slated attribute contains an optout, stop.
    if (in_array($optout, $attribute)) {
      $this->stopPipeline();
      return NULL;
    }

    // Otherwise, return the original value unchanged.
    return $value;
  }

}
