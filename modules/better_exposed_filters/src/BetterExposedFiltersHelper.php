<?php

namespace Drupal\better_exposed_filters;

use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a helper class for better exposed filters.
 */
class BetterExposedFiltersHelper {

  /**
   * Rewrites a set of options given a string from the config form.
   *
   * Rewrites should be specified, one per line, using the format
   * old_string|new_string. If new_string is empty, the option will be removed.
   *
   * @param array $options
   *   An array of key => value pairs that may be rewritten.
   * @param string $rewrite_settings
   *   String representing the entry in the settings form.
   * @param bool $reorder
   *   Reorder $options based on the rewrite settings.
   * @param bool $rewrite_based_on_key
   *   Flag determining whether options labels should be rewritten based on key.
   *
   * @return array
   *   Rewritten $options.
   */
  public static function rewriteOptions(array $options, string $rewrite_settings, bool $reorder = FALSE, bool $rewrite_based_on_key = FALSE): array {
    // Break out early if we don't have anything to rewrite.
    if (empty($rewrite_settings)) {
      return $options;
    }

    $rewrites = [];
    $order = [];
    $return = [];

    // Get a copy of the option, flattened with their keys preserved.
    $flat_options = self::flattenOptions($options, TRUE);

    // Preserve order.
    if (!$reorder) {
      $order = array_keys($options);
    }

    $lines = explode("\n", trim($rewrite_settings));
    foreach ($lines as $line) {
      [$search, $replace] = array_map('trim', explode('|', $line));
      $rewrites[$search] = $replace;

      // Find the key of the option we need to reorder.
      if ($reorder) {
        $key = array_search($search, $flat_options);
        if ($key !== FALSE) {
          $order[] = $key;
        }
      }
    }

    // Reorder options in the order they are specified in rewrites.
    // Collect the keys to use later.
    if ($reorder && !empty($order)) {
      // Start with the items that were listed in the rewrite settings.
      foreach ($order as $key) {
        $return[$key] = $options[$key];
        unset($options[$key]);
      }

      // Tack remaining options on the end.
      $return += $options;
    }
    else {
      $return = $options;
    }

    // Rewrite the option value.
    foreach ($return as $index => &$choice) {
      if ($rewrite_based_on_key) {
        if (isset($rewrites[$index])) {
          // phpcs:ignore
          $return[$index] = new TranslatableMarkup($rewrites[$index]);
        }
      }
      else {
        if (is_object($choice) && isset($choice->option)) {
          $key = key($choice->option);
          $value = &$choice->option[$key];
        }
        elseif (is_array($choice) && array_key_exists('name', $choice)) {
          $value = &$choice['name'];
        }
        else {
          $choice = (string) $choice;
          $value = &$choice;
        }

        if (!is_scalar($value)) {
          // We give up...
          continue;
        }

        if (isset($rewrites[$value])) {
          if ('' === $rewrites[$value]) {
            unset($return[$index]);
          }
          else {
            $value = $rewrites[$value];
          }
        }
      }
    }
    return $return;
  }

  /**
   * Flattens list of mixed options into a simple array of scalar value.
   *
   * @param array $options
   *   List of mixed options - scalar, translatable markup or taxonomy term
   *   options.
   * @param bool $preserve_keys
   *   TRUE if the original keys should be preserved, FALSE otherwise.
   *
   * @return array
   *   Flattened list of scalar options.
   */
  public static function flattenOptions(array $options, bool $preserve_keys = FALSE): array {
    $flat_options = [];

    foreach ($options as $key => $choice) {
      if (is_array($choice)) {
        $flat_options = array_merge($flat_options, self::flattenOptions($choice));
      }
      elseif (is_object($choice) && isset($choice->option)) {
        $key = $preserve_keys ? $key : key($choice->option);
        $flat_options[$key] = current($choice->option);
      }
      else {
        $flat_options[$key] = (string) $choice;
      }
    }
    return $flat_options;
  }

  /**
   * Sort options alphabetically.
   *
   * @param array $options
   *   Array of unsorted options - scalar, translatable markup or taxonomy term
   *   options.
   *
   * @return array
   *   Alphabetically sorted array of original values.
   */
  public static function sortOptions(array $options): array {
    // Flatten array of mixed values to a simple array of scalar values.
    $flat_options = self::flattenOptions($options, TRUE);

    // Alphabetically sort our list of concatenated values.
    uasort($flat_options, function ($a, $b) {
      $transliteration = \Drupal::transliteration();
      return strnatcasecmp($transliteration->transliterate($a), $transliteration->transliterate($b));
    });

    // Now use its keys to sort the original array.
    return array_replace(array_flip(array_keys($flat_options)), $options);
  }

  /**
   * Sort nested options alphabetically.
   *
   * @param array $options
   *   Array of nested unsorted options - scalar, translatable markup or
   *   taxonomy term options.
   * @param string $delimiter
   *   The delimiter used to indicate nested level. (e.g. -Seattle)
   *
   * @return array
   *   Alphabetically sorted array of original values.
   */
  public static function sortNestedOptions(array $options, string $delimiter = '-'): array {
    // Flatten array of mixed values to a simple array of scalar values.
    $flat_options = self::flattenOptions($options, TRUE);
    $prev_key = NULL;
    $level = 0;
    $parent = [$level => ''];

    // Iterate over each option.
    foreach ($flat_options as $key => &$choice) {
      // For each option, determine the nested level based on the delimiter.
      // Example:
      // - 'United States' will have level 0.
      // - '-Seattle' will have level 1.
      $cur_level = strlen($choice) - strlen(ltrim($choice, $delimiter));

      // If we are going down a level, keep track of its parent value.
      if ($cur_level > $level) {
        $parent[$cur_level] = $flat_options[$prev_key];
      }

      // Prepend each option value with its parent for easier sorting.
      // Example:
      // '-Seattle' is below 'United States', its concatenated value will become
      // 'United States-Seattle' etc...
      $choice = $parent[$cur_level] . $choice;

      // Update level and prev_key.
      $level = $cur_level;
      $prev_key = $key;
    }

    // Alphabetically sort our list of concatenated values.
    uasort($flat_options, function ($a, $b) {
      $transliteration = \Drupal::transliteration();
      return strnatcasecmp($transliteration->transliterate($a), $transliteration->transliterate($b));
    });

    // Now use its keys to sort the original array.
    return array_replace(array_flip(array_keys($flat_options)), $options);
  }

}
