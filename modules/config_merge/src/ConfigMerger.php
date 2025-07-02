<?php

namespace Drupal\config_merge;

use Symfony\Component\Yaml\Inline;

/**
 * Provides helper functions for merging configuration items.
 */
class ConfigMerger {

  /**
   * Indicates update operation during config merge.
   *
   * Merge is successful, there were changes and they were merged in.
   */
  const OPERATION_UPDATE = 'update';

  /**
   * Indicates ignore operation during config merge. Default operation.
   *
   * 3 way merge was not possible, because module has changes and active storage
   * was changed too.
   */
  const OPERATION_IGNORE = 'ignore';

  /**
   * Indicates substitute (replace) operation during config merge.
   *
   * In case of indexed(non-associated) arrays configuration is substituted
   * (replaced) completely if the data is unchanged.
   */
  const OPERATION_SUBSTITUTE = 'substitute';

  /**
   * Config merge results log.
   *
   * @var array
   */
  protected static $logs = [];

  /**
   * Gets the logs of config merge results.
   *
   * @return array
   *   Array of logs with operations on config as keys.
   */
  public static function getLogs() {
    return self::$logs;
  }

  /**
   * Merges changes to a configuration item into the active storage.
   *
   * @param array $previous
   *   The configuration item as previously provided (from snapshot).
   * @param array $current
   *   The configuration item as currently provided by an extension.
   * @param array $active
   *   The configuration item as present in the active storage.
   * @param array $parent_keys
   *   The keys of the property being merged in case of nested structure.
   * @param int $level
   *   The level of recursion.
   *
   * @return array
   *   Merged configuration.
   */
  public static function mergeConfigItemStates(array $previous, array $current, array $active, array $parent_keys = [], $level = 0) {
    if ($level === 0) {
      self::$logs = [];
    }
    // We are merging into the active configuration state.
    $result = $active;

    $states = [
      $previous,
      $current,
      $active,
    ];

    $is_associative = FALSE;

    foreach ($states as $array) {
      // Analyze the array to determine if we should preserve integer keys.
      if (Inline::isHash($array)) {
        // If any of the states is associative, treat the item as associative.
        $is_associative = TRUE;
        break;
      }
    }

    // Process associative arrays.
    // Find any differences between previous and current states.
    if ($is_associative) {
      // Detect and process removals.
      $removed = array_diff_key($previous, $current);
      foreach ($removed as $key => $value) {
        // Remove only if unchanged in the active state.
        if (isset($active[$key]) && $active[$key] === $previous[$key]) {
          unset($result[$key]);
        }
      }

      // Detect and handle additions.
      // Additions are keys added since the previous state and not overridden
      // in the active state.
      $added = array_diff_key($current, $previous, $active);
      // Merge in all current keys while retaining the key order.
      $merged = array_replace($current, $result);
      // Filter to keep array items from the merged set that ...
      $result = array_intersect_key(
        // Have keys that are either ...
        $merged, array_flip(
          array_merge(
            // In the original result set or ...
            array_keys($result),
            // Should be added.
            array_keys($added)
          )
        )
      );

      // Detect and process changes.
      foreach ($current as $key => $value) {
        if (isset($previous[$key]) && $previous[$key] !== $value) {
          // If we have an array, recurse.
          if (is_array($value) && is_array($previous[$key]) && isset($active[$key]) && is_array($active[$key])) {
            $recursion_keys = $parent_keys;
            $recursion_keys[] = $key;
            $level++;
            $result[$key] = self::mergeConfigItemStates($previous[$key], $value, $active[$key], $recursion_keys, $level);
          }
          else {
            $operation = static::OPERATION_IGNORE;
            // Accept the new value only if the item hasn't been customized.
            if (isset($active[$key]) && $active[$key] === $previous[$key]) {
              $result[$key] = $value;
              $operation = static::OPERATION_UPDATE;
            }
            self::$logs[$operation][] = [
              'name' => $key,
              'state' => [
                'active' => $active[$key],
                'previous' => $previous[$key],
                'new' => $value,
              ],
              'parents' => $parent_keys,
            ];
          }
        }
      }
    }
    // Process indexed arrays. Here we can't reliably distinguish between an
    // array value that's been changed and one that is new. Therefore, rather
    // than merging array values, we return either the active or the current
    // (new) state.
    else {
      $operation = static::OPERATION_IGNORE;
      // If the data is unchanged, use the current value. Otherwise, retain any
      // customization by keeping with the active value set above.
      if ($previous === $active) {
        $result = $current;
        $operation = static::OPERATION_SUBSTITUTE;
      }
      self::$logs[$operation][] = [
        'name' => end($parent_keys),
        'state' => [
          'active' => $active,
          'previous' => $previous,
          'new' => $current,
        ],
        'parents' => $parent_keys,
      ];
    }

    return $result;
  }

}
