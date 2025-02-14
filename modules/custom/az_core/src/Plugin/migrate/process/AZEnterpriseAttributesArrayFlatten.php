<?php

declare(strict_types=1);

namespace Drupal\az_core\Plugin\migrate\process;

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

    // Include all first-level keys.
    $result = array_keys($input);

    foreach ($input as $value) {
      if (is_array($value) && isset($value[0]) && is_string($value[0])) {
        $result = array_merge($result, array_map('trim', explode(',', $value[0])));
      }
    }

    return array_values(array_unique($result));
  }

}
