<?php

namespace Drupal\az_event_trellis\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\FormatDate;
use Drupal\migrate\Row;

/**
 * Process plugin for handling Trellis Event date formats.
 *
 * Extends core format_date plugin to be able to use a different from_timezone
 * value for each row.
 *
 * @MigrateProcessPlugin(
 *   id = "az_trellis_format_date"
 * )
 */
class TrellisFormatDate extends FormatDate {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (isset($this->configuration['source_timezone'])) {
      $timezone = $row->get($this->configuration['source_timezone']);
      if (isset($timezone)) {
        $this->configuration['from_timezone'] = $timezone;
      }
    }

    return parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
