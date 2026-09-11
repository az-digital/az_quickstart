<?php

namespace Drupal\az_person_profiles_import\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides an "Empty Coalesce" process plugin.
 *
 * Given a set of values provided to the plugin, the plugin will return the
 * first non-empty value.
 *
 * This is needed over null_coalesce because the JSON parser returns empty
 * values for missing selectors rather than null.
 *
 * Available configuration keys:
 * - source: The input array.
 * - default_value: (optional) The value to return if all values are empty.
 *   if not provided, NULL is returned if all values are empty.
 *
 * Example:
 * Given source keys of foo, bar, and baz:
 * @code
 * process_key:
 *   plugin: az_person_profiles_empty_coalesce
 *   source:
 *     - foo
 *     - bar
 *     - baz
 * @endcode
 */
#[MigrateProcess('az_person_profiles_empty_coalesce')]
class AZPersonEmptyCoalesce extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_array($value)) {
      throw new MigrateException("The input value should be an array.");
    }
    foreach ($value as $val) {
      if (!empty($val)) {
        return $val;
      }
    }
    if (isset($this->configuration['default_value'])) {
      return $this->configuration['default_value'];
    }
    return NULL;
  }

}
