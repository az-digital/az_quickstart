<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Attempts to convert a string to a phone number.
 *
 * Example:
 *
 * @code
 * process:
 *   field_az_phone:
 *     plugin: az_phone
 *     source: phone
 * @endcode
 */
#[MigrateProcess(
  id: 'az_phone',
  handle_multiples: TRUE,
)]
class PhoneProcess extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $phones = [];

    if (empty($value)) {
      return $phones;
    }
    $was_array = FALSE;
    if (!is_array($value)) {
      $was_array = TRUE;
      $value = [$value];
    }
    foreach ($value as $phone) {
      $formatted = [];
      // Profiles API phones are nested.
      $phone = $phone['number'] ?? $phone;
      // We'll attempt to do some formatting on the phone number.
      // Match hoping to split up the relevant digits.
      if (preg_match('/^(\+\d{1,2}\s?)?(\(?\d{3}\)?)[\s.-]?(\d{3})[\s.-]?(\d{4})$/', $phone, $formatted)) {
        array_shift($formatted);
        $number = vsprintf("%s (%s) %s-%s", $formatted);
        // Area code might be empty.
        $phone = trim(str_replace('()', '', $number));
      }
      $phones[] = $phone;
    }

    if (!$was_array) {
      $phones = reset($phones);
    }
    return $phones;
  }

}
