<?php

declare(strict_types = 1);

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Flattens a multi-dimensional array.
 */
#[MigrateProcess('az_flatten_enterprise_attributes_array')]
class FlattenEnterpriseAttributesArray extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($input, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($input)) {
      return $input;
    }

    $result = array_keys($input); // Include all first-level keys.

    foreach ($input as $value) {
      if (is_array($value) && isset($value[0]) && is_string($value[0])) {
        // Flatten comma-separated values found in the first element of each array.
        $result = array_merge($result, array_map('trim', explode(',', $value[0])));
      }
    }

    return array_values(array_unique($result));
  }

}
