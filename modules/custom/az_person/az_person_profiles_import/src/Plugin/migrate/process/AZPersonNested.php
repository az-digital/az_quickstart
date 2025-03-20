<?php

namespace Drupal\az_person_profiles_import\Plugin\migrate\process;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Returns an ordered items from Profiles API data.
 *
 * Available configuration keys
 * - source: A source item to draw from.
 * - sort: An array of parent keys to an element to sort by per row.
 * - split: An array of parent keys to an array to duplicate.
 * - format: An array of maps the specify individual sections of output.
 *
 * - Within format:
 *   - keys: An array of parent keys to a value you want to display.
 *   - peoplesoft (optional) TRUE if the value is a peoplesoft term.
 *   - skip_if_empty: (optional) TRUE if empty items should not display.
 *   - filter: (optional) A map of values to require in the item.
 *   - prefix: (optional) A string to render before the item.
 *   - suffix: (optional) A string to render after the item.
 * @code
 * process:
 *   field_az_awards:
 *     plugin: az_person_profiles_nested
 *     source: awards
 *     split:
 *       - details
 *     sort:
 *       - details
 *       - CONFERRED_AWARD
 *       - start_term
 *     format:
 *       -
 *         keys:
 *           - properties
 *           - name
 *       -
 *         prefix: ', '
 *         skip_if_empty: TRUE
 *         keys:
 *           - details
 *           - institution
 *           - name
 *       -
 *         prefix: ', '
 *         skip_if_empty: TRUE
 *         peoplesoft: TRUE
 *         keys:
 *           - details
 *           - CONFERRED_AWARD
 *           - start_term
 * @endcode
 *
 * In the above example, awards often have multiple details
 * per award, so split is used to clone the data. This means we
 * get one row PER award PER details.
 *
 * Awards are sorted by start_term, which is nested within
 * details and CONFERRED_AWARD in the source data.
 *
 * This example displays three items:
 * name (nested within properties)
 * name (nested within details and institution)
 * start_term (nested within details and CONFERRED AWARD)
 *
 * The institution name and start_term are prefixed by commas.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess(
  id: "az_person_profiles_nested",
  handle_multiples: TRUE,
)]

class AZPersonNested extends ProcessPluginBase {

  /**
   * Expands a peoplesoft term code to its string representation.
   *
   * This function function splits up peoplesoft term codes
   * which by default are in CYYT format:
   * century, year, year, term code.
   *
   * @param string $value
   *   A term name and year, or perhaps the original value upon failure.
   */
  protected function peoplesoft($value) {

    // PeopleSoft uses CYYT, century, year year, term code.
    // Summer II is no longer used and referred to as Summer.
    $terms = [
      '1' => 'Spring ',
      '2' => 'Summer ',
      // Actually, Summer II.
      '3' => 'Summer ',
      '4' => 'Fall ',
      '5' => 'Winter ',
    ];

    $matches = [];
    if (preg_match('/^(\d)(\d\d)(\d)$/', $value, $matches)) {
      // Start with century.
      $year = 1800 + ((int) $matches[1] * 100);
      // Years.
      $year += (int) $matches[2];
      $code = (string) $matches[3];
      $year = (string) $year;
      // Term code.
      if (!empty($terms[$code])) {
        $year = $terms[$code] . $year;
      }
      $value = $year;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get plugin configuration.
    $format = $this->configuration['format'] ?? [];
    $filter = $this->configuration['filter'] ?? [];
    $split = $this->configuration['split'] ?? [];
    $processed = [];

    // See if we've been requested to duplicate rows on a key.
    if (!empty($split)) {
      $new_value = [];
      foreach ($value as $item) {
        // Fetch the requested split and deuplicate the rows.
        $children = NestedArray::getValue($item, $split) ?? [];
        foreach ($children as $child) {
          $clone = $item;
          NestedArray::setValue($clone, $split, $child, TRUE);
          $new_value[] = $clone;
        }
      }
      $value = $new_value;
    }

    // Sort configuration.
    $sort = $this->configuration['sort'] ?? [];
    if (!empty($sort)) {
      // Sort by specified parameters.
      usort($value, function ($a, $b) use ($sort) {
        $a_sort = NestedArray::getValue($a, $sort) ?? '';
        $b_sort = NestedArray::getValue($b, $sort) ?? '';
        return strcmp($b_sort, $a_sort);
      });
    }

    // Loop through input, building items as we go.
    foreach ($value as $item) {
      $result = '';
      // Check if the item has specified filter values.
      $meets_conditions = TRUE;
      foreach ($filter as $filter_key => $filter_value) {
        $found_value = $item[$filter_key] ?? NULL;
        if ($found_value !== $filter_value) {
          $meets_conditions = FALSE;
        }
      }
      if (!$meets_conditions) {
        continue;
      }
      foreach ($format as $f) {
        if (empty($f['keys'])) {
          continue;
        }
        // Get configuration settings per element.
        $skip = $f['skip_if_empty'] ?? FALSE;
        $peoplesoft = $f['peoplesoft'] ?? FALSE;
        $prefix = $f['prefix'] ?? '';
        $suffix = $f['suffix'] ?? '';
        $keys = $f['keys'];

        // Default in case the item isn't found.
        $default = $f['default'] ?? '';
        // Lookup the deeply nested value.
        $lookup = (string) NestedArray::getValue($item, $keys) ?? $default;

        // Remove stray HTML entities.
        $lookup = Html::decodeEntities($lookup);

        // Handle Peoplesoft values.
        if ($peoplesoft) {
          $lookup = $this->peoplesoft($lookup);
        }

        // Handle skip logic.
        if (!empty($lookup) || !$skip) {
          $result .= $prefix . $lookup . $suffix;
        }
      }
      $processed[] = $result;
    }
    return $processed;
  }

}
