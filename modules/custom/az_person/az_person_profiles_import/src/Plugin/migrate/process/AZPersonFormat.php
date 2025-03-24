<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Restripes a simple array as a value/format array.
 *
 * Available configuration keys
 * - source: A source item to draw from.
 * - format: A text format to use.
 *
 * @code
 *   field_az_teaching_interests:
 *     plugin: az_person_profiles_format
 *     source: interests
 *     format: plain_text
 * @endcode
 */
#[MigrateProcess(
  id: "az_person_profiles_format",
  handle_multiples: TRUE,
)]
class AZPersonFormat extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If existing value is empty, return an empty array
    // JSON parser can return an empty string if a selector is empty.
    if (empty($value)) {
      return [];
    }

    // If value is not an array, wrap it.
    if (!is_array($value)) {
      $value = [$value];
    }

    // Determine format value to use.
    $format = $this->configuration['format'] ?? 'plain_text';
    $new_value = [];

    // Compute new values with formats added.
    foreach ($value as $v) {
      $new_value[] = [
        'value' => $v,
        'format' => $format,
      ];
    }
    return $new_value;
  }

}
