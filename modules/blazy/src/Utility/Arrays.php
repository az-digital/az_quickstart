<?php

namespace Drupal\blazy\Utility;

use Drupal\Component\Utility\NestedArray;

/**
 * Provides blazy utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Arrays {

  /**
   * Filters out empty string value to avoid JSON.parse error.
   */
  public static function filter(array $config): array {
    return array_filter($config, '\Drupal\blazy\Utility\Arrays::filterEmpty');
  }

  /**
   * Filters out empty string value to avoid JSON.parse error.
   */
  public static function filterEmpty($config): bool {
    return ($config !== NULL && $config !== '' && $config !== []);
  }

  /**
   * Merge data with a new one with an optional key and reversed parameters.
   */
  public static function merge(array $data, array $element, $key = NULL): array {
    if ($key) {
      return empty($element[$key])
        ? $data : NestedArray::mergeDeep($element[$key], $data);
    }
    return empty($element)
      ? $data : NestedArray::mergeDeep($element, $data);
  }

  /**
   * Merge multiple BlazySettings objects.
   */
  public static function mergeSettings($keys, array $defaults, array $configs): array {
    $keys = is_string($keys) ? [$keys] : $keys;
    foreach ($keys as $key) {
      $object = $defaults[$key] ?? NULL;
      $oldies = $object ? $object->storage() : [];

      if (!isset($configs[$key]) && $object) {
        $configs[$key] = $object;
      }

      if ($newbies = $configs[$key] ?? NULL) {
        $data = $newbies->storage();
        $data = $oldies ? NestedArray::mergeDeepArray([$oldies, $data], TRUE) : $data;
        $configs[$key]->setData($data);
      }
    }

    return $configs;
  }

}
