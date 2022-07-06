<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to handle migrating Drupal datetime field to smart_date field.
 *
 * Expects a multidimentional array typical of a Drupal datetime field.
 *
 * Available configuration keys
 * - source_start: (required) Field to copy start time from.
 * - source_end: (optional) Field to copy end time from, if not set the
 *   source_start field value will be used.
 * - default_duration: (int, optional) If no end date, assumed duration.
 *
 * Consider a datetime field migration
 * @code
 *   process:
 *     field_az_event_date:
 *       - plugin: single_value
 *         source: field_uaqs_event_date
 *       - plugin: az_drupal_date_to_smart_date
 *         source_start: value
 *         source_end: value2
 *         timezone: 'America/Phoenix'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "az_datetime_to_smart_date"
 * )
 */
class DateTimeToSmartDate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (empty($this->configuration['source_start'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'source_start' is required."
      );
    }
    if (isset($this->configuration['default_duration']) && !is_numeric($this->configuration['default_duration'])) {
      throw new InvalidPluginDefinitionException(
        $this->getPluginId(),
        "Configuration option 'default_duration' should be a numeric represention of a length of time in minutes,
        for example 60 is one minute."
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    $duration = 0;
    $timezone = '';
    if (isset($this->configuration['default_duration'])) {
      $def_duration = (int) $this->configuration['default_duration'];
    }
    else {
      $def_duration = 0;
    }
    $utc = new \DateTimeZone('UTC');
    if (is_array($value) || $value instanceof \Traversable) {
      foreach ($value as $delta => $date) {
        $source_start = $date[$this->configuration['source_start']];
        $start_date = $source_start;
        if (isset($this->configuration['source_end']) && isset($date[$this->configuration['source_end']])) {
          $source_end = $date[$this->configuration['source_end']];
        }
        else {
          $source_end = $source_start;
        }
        $start_date = new \DateTime($start_date, $utc);
        $start_date = $start_date->format('U');

        // Remove any seconds from the incoming value.
        $start_date -= $start_date % 60;

        $end_date = NULL;
        // Assume a datetime range, so look for the end_value.
        if (!empty($source_end)) {
          $end_date = $source_end;
        }
        if (!empty($end_date)) {
          $end_date = new \DateTime($end_date, $utc);
          $end_date = $end_date->format('U');

          // Remove any seconds from the incoming value.
          $end_date -= $end_date % 60;

          // If valid end date, set duration. Otherwise make a new end date.
          if ($start_date < $end_date) {
            $duration = (int) round(($end_date - $start_date) / 60);
          }
          else {
            $end_date = $start_date;
            $duration = (int) round(($end_date - $start_date) / 60);
          }

          if (!$end_date) {
            // If the end date is bogus, use default duration.
            $end_date = $start_date + ($def_duration * 60);
            $duration = $def_duration;
          }
          if (isset($this->configuration['timezone'])) {
            $timezone = $this->configuration['timezone'];
          }
        }
        $value[$delta] = [
          'value' => $start_date,
          'end_value' => $end_date,
          'duration' => $duration,
          'rrule' => NULL,
          'rrule_index' => NULL,
          'timezone' => $timezone,
        ];
      }
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}
