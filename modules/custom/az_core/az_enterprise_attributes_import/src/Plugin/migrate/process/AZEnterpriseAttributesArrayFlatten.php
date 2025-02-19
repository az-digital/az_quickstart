<?php

declare(strict_types=1);

namespace Drupal\az_enterprise_attributes_import\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Flattens a multi-dimensional array.
 *
 * @code
 * process:
 *   field_of_array_values:
 *   - plugin: az_enterprise_attributes_flatten
 *     source: enterprise_attributes_array
 *   - plugin: flatten
 * @endcode
 */
#[MigrateProcess('az_enterprise_attributes_flatten')]
class AZEnterpriseAttributesArrayFlatten extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($input, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($input)) {
      return $input;
    }

    $result = [];
    $exceptions = [
      'Lectures, Workshops & Panels',
      'Tours, Attractions & Exhibits',
    ];

    foreach ($input as $value) {
      if (is_array($value) && isset($value[0]) && is_string($value[0])) {
        $string = trim($value[0]);

        // Preserve known exceptions, otherwise split by commas.
        if (in_array($string, $exceptions, TRUE)) {
          $result[] = $string;
        }
        else {
          $result = array_merge($result, array_map('trim', explode(',', $string)));
        }
      }
    }

    return array_values(array_unique($result));
  }

}
