<?php

declare(strict_types=1);

namespace Drupal\config_split\Config;

use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\Schema\Sequence;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * The patch merging service.
 *
 * @internal This is not an API, anything here might change without notice. Use config_merge 2.x instead.
 */
class ConfigPatchMerge {

  /**
   * The sorter service.
   *
   * @var \Drupal\config_split\Config\ConfigSorter
   */
  protected $configSorter;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The service constructor.
   *
   * @param \Drupal\config_split\Config\ConfigSorter $configSorter
   *   The sorter.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   */
  public function __construct(ConfigSorter $configSorter, TypedConfigManagerInterface $typedConfigManager) {
    $this->configSorter = $configSorter;
    $this->typedConfigManager = $typedConfigManager;
  }

  /**
   * Create a patch object given two arrays.
   *
   * @param array $original
   *   The original data.
   * @param array $new
   *   The new data.
   * @param string $name
   *   The name of the config.
   *
   * @return \Drupal\config_split\Config\ConfigPatch
   *   The patch object.
   */
  public function createPatch(array $original, array $new, string $name): ConfigPatch {
    /** @var \Drupal\Core\Config\Schema\Element $originalElement */
    $originalElement = $this->typedConfigManager->createFromNameAndData($name, $original);
    /** @var \Drupal\Core\Config\Schema\Element $newElement */
    $newElement = $this->typedConfigManager->createFromNameAndData($name, $new);
    return ConfigPatch::fromArray([
      'added' => self::diffArray($new, $original, $newElement, FALSE),
      'removed' => self::diffArray($original, $new, $originalElement, FALSE),
    ]);
  }

  /**
   * Apply a patch to a config array.
   *
   * @param array $config
   *   The config data.
   * @param \Drupal\config_split\Config\ConfigPatch $patch
   *   The patch object.
   * @param string $name
   *   The config name to sort it correctly.
   *
   * @return array
   *   The changed config data.
   */
  public function mergePatch(array $config, ConfigPatch $patch, string $name): array {
    if ($patch->isEmpty()) {
      return $config;
    }
    /** @var \Drupal\Core\Config\Schema\Element $element */
    $element = $this->typedConfigManager->createFromNameAndData($name, $config);

    $changed = self::diffArray($config, $patch->getRemoved(), $element, TRUE);
    $changed = self::mergeArray($changed, $patch->getAdded(), $element);
    $changed = self::removeSequenceKeys($changed);

    // Use the sorter to make sure the patch is applied correctly.
    $changed = $this->configSorter->sort($name, $changed);

    return $changed;
  }

  /**
   * Recursively computes the difference of arrays.
   *
   * This method transforms the sequence keys and then acts like the utility.
   *
   * @param array $array1
   *   The array to compare from.
   * @param array $array2
   *   The array to compare to.
   * @param \Drupal\Core\Config\Schema\Element $element
   *   The typed config element.
   * @param bool $merging
   *   True while merging, false while creating a patch.
   * @param string $path
   *   The path into the config.
   *
   * @return array
   *   Returns an array containing all the values from array1 that are not
   *   present in array2.
   *
   * @see \Drupal\Component\Utility\DiffArray::diffAssocRecursive()
   */
  private static function diffArray(array $array1, array $array2, Element $element, bool $merging, string $path = ''): array {
    // This should not be necessary, but somehow objects have been part of it.
    self::handleStrayObjects($array1);
    self::handleStrayObjects($array2);

    // Make sure that all keys are strings addressing the elements.
    $keysMatter = TRUE;
    $type = self::getType($element, $path);
    if ($type instanceof Sequence || self::isSequence($array1, $array2)) {
      $keysMatter = FALSE;
      $array1 = self::transformSequenceKeys($array1, $type, $keysMatter);
      $array2 = self::transformSequenceKeys($array2, $type, $keysMatter);
    }

    $i = 0;
    $diff = [];
    foreach ($array1 as $key => $value) {
      if (is_array($value)) {
        if (!array_key_exists($key, $array2) || !is_array($array2[$key])) {
          $diff[$key] = $value;
        }
        else {
          // Fix the lookup key and recurse.
          $lookup_key = strpos((string) $key, 'config_split_sequence') === 0 ? $i : $key;
          $lookup_key = empty($path) ? $lookup_key : $path . '.' . $lookup_key;
          $new_diff = self::diffArray($value, $array2[$key], $element, $merging, $lookup_key);
          if (!empty($new_diff)) {
            $diff[$key] = $new_diff;
          }
          elseif ($merging) {
            // When we are merging the patch we check if the type is a mapping.
            if ($type instanceof Mapping) {
              // For mappings, we keep the element with an empty array.
              $diff[$key] = [];
              // @todo find a better way to know which elements are required.
              if ($type->getDataDefinition()->getDataType() === 'config_dependencies') {
                // Except for sub keys of dependencies.
                unset($diff[$key]);
              }
            }
          }
        }
      }
      elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
        $diff[$key] = $value;
      }
      $i++;
    }

    if (!$keysMatter) {
      // If there are no duplicates we can go back to integer keys for a more
      // natural patch export.
      $diff = array_values($diff);
    }

    return $diff;
  }

  /**
   * Merges two arrays recursively.
   *
   * @param array $array1
   *   The first array.
   * @param array $array2
   *   The second array.
   * @param \Drupal\Core\Config\Schema\Element $element
   *   The typed config element.
   * @param string $path
   *   The path into the config.
   *
   * @return array
   *   The merged array.
   *
   * @see \Drupal\Component\Utility\NestedArray::mergeDeepArray()
   */
  private static function mergeArray(array $array1, array $array2, Element $element, string $path = ''): array {
    $type = self::getType($element, $path);
    if ($type instanceof Sequence || self::isSequence($array1, $array2)) {
      $array1 = self::transformSequenceKeys($array1, $type);
      $array2 = self::transformSequenceKeys($array2, $type);
    }

    $result = [];
    foreach ([$array1, $array2] as $array) {
      $i = 0;
      foreach ($array as $key => $value) {
        // Recurse when both values are arrays.
        if (isset($result[$key]) && is_array($result[$key]) && is_array($value)) {
          // Fix the lookup key.
          $lookup_key = strpos((string) $key, 'config_split_sequence') === 0 ? $i : $key;
          $lookup_key = empty($path) ? $lookup_key : $path . '.' . $lookup_key;
          $result[$key] = self::mergeArray($result[$key], $value, $element, $lookup_key);
        }
        // Otherwise, use the latter value, overriding any previous value.
        else {
          $result[$key] = $value;
        }
        $i++;
      }
    }

    // The merged array is cleaned up after the merge is complete.
    return $result;
  }

  /**
   * Check if data is a sequence.
   *
   * @param array $array1
   *   The data.
   * @param array $array2
   *   The data.
   *
   * @return bool
   *   True if the input is considered a sequence.
   */
  private static function isSequence(array $array1, array $array2): bool {
    // Both need to be indexed arrays or have escaped keys.
    return self::isList(self::removeSequenceKeys($array1)) && self::isList(self::removeSequenceKeys($array2));
  }

  /**
   * Get the typed data of a schema element.
   *
   * @param \Drupal\Core\Config\Schema\Element $element
   *   The typed config element.
   * @param string $path
   *   The path into the config.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface|mixed
   *   The typed data.
   */
  private static function getType(Element $element, string $path) {
    if ($path === '') {
      return $element;
    }
    if ($element instanceof ArrayElement && !empty($path)) {
      try {
        return $element->get($path);
      }
      catch (\InvalidArgumentException $exception) {
        // Something went wrong, we can not use the type.
      }
    }

    return NULL;
  }

  /**
   * Check if given array is a normal indexed array or not.
   *
   * @param array|\ArrayObject|object $value
   *   The PHP array or array-like object to check.
   *
   * @return bool
   *   True if value is a sequence array, false otherwise.
   */
  private static function isList($value): bool {
    // In Drupal 10 (php 8.1) this can be replaced with array_is_list.
    $expected_key = 0;
    foreach ($value as $key => $val) {
      if ($key !== $expected_key++) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * Transforms sequential keys into unique string keys.
   *
   * @param array $array
   *   The array to transform.
   * @param \Drupal\Core\TypedData\TypedDataInterface|mixed $type
   *   The type.
   * @param bool $keysMatter
   *   Will be set to true if there are duplicate entries or keys from uuids.
   *
   * @return array
   *   The transformed array.
   */
  private static function transformSequenceKeys(array $array, $type, bool &$keysMatter = FALSE): array {
    $transformed = [];
    $callback = NULL;

    if ($type instanceof Sequence) {
      // Clone the type and set the value.
      // The $array may contain the wrong keys if they had been transformed
      // already. The loop will catch that and not actually use the callback in
      // that case.
      $type = clone $type;
      $type->setValue($array, FALSE);
      $callback = self::getPatchIndexCallback($type, $keysMatter);
    }

    foreach ($array as $key => $value) {
      if (strpos((string) $key, 'config_split_sequence_') === 0) {
        // Do not transform already transformed data.
        $keysMatter = TRUE;
        return $array;
      }

      if (is_callable($callback)) {
        $candidate = call_user_func($callback, $value, $key);
      }
      else {
        if (!is_numeric($key)) {
          // This check ensures we only transform sequences with numeric keys.
          $keysMatter = TRUE;
          return $array;
        }

        $candidate = $value;
        if (is_array($value)) {
          if (isset($value['uuid'])) {
            // This is essentially setting the patch index to "uuid" when the
            // sequence contains a mapping with a uuid field.
            $keysMatter = TRUE;
            $candidate = $value['uuid'];
          }
          else {
            // For sequences that are arrays without uuids and for which there
            // is no patch index callback, we use the key and accept that it
            // will inevitably not work when things are moved around.
            // Using the $value as the candidate would mean that if anything at
            // all changes the whole array would be considered different.
            $candidate = $key;
          }
        }
      }

      // We don't know what the data is, so we make sure it can be used as a
      // yaml key by hashing it. We shorten the hash because it will be enough.
      $candidate = substr(sha1(serialize($candidate)), 0, 20);
      // Shorten the string by using more letters.
      $candidate = implode('', array_map(function ($piece) {
        // Five characters of base 16 fit into 4 characters of base 32.
        return base_convert($piece, 16, 32);
      }, str_split($candidate, 5)));

      $i = 1;
      $key = 'config_split_sequence_' . $candidate;
      while (array_key_exists($key, $transformed)) {
        $keysMatter = TRUE;
        $key = 'config_split_sequence_' . $i . '_' . $candidate;
        $i++;
      }

      $transformed[$key] = $value;
    }

    return $transformed;
  }

  /**
   * Recursively removes sequence keys added by self::transformSequenceKeys().
   *
   * @param array $array
   *   The array to remove sequence keys from.
   *
   * @return array
   *   The cleaned up array.
   */
  private static function removeSequenceKeys(array $array): array {
    $sequence = FALSE;
    foreach ($array as $key => &$value) {
      if (strpos((string) $key, 'config_split_sequence_') === 0) {
        $sequence = TRUE;
      }
      if (is_array($value)) {
        $value = self::removeSequenceKeys($value);
      }
    }
    if ($sequence) {
      return array_values($array);
    }
    return $array;
  }

  /**
   * Get the callback to generate the patch key.
   *
   * @param \Drupal\Core\Config\Schema\Sequence $type
   *   The sequence.
   * @param bool $keysMatter
   *   The value to indicate if the patch key matters.
   *
   * @return callable|null
   *   The callable to generate the key.
   */
  private static function getPatchIndexCallback(Sequence $type, bool &$keysMatter) {
    // We expect this to be a string.
    $setting = (string) $type->getDataDefinition()->getSetting('patch index');
    if ($setting === '') {
      // The index is not defined.
      return NULL;
    }

    if ($setting === '*') {
      // The entire sequence element is considered for the key.
      return function ($value) {
        return $value;
      };
    }
    // Explode the string so that multiple keys could be used.
    $setting = explode(':', $setting);
    $keysMatter = TRUE;

    return function ($value, $key) use ($setting, $type) {
      $keep = [];
      foreach ($setting as $item) {
        // Get the value from the typed data.
        // If there is something unexpected this will throw an exception.
        $keep[$item] = $type->get($key . '.' . $item)->getValue();
      }

      return $keep;
    };
  }

  /**
   * Make sure arrays do not contain objects.
   *
   * @param array $array
   *   The array to massage.
   */
  private static function handleStrayObjects(array &$array) {
    array_walk($array, function (&$value, $key) {
      // It is still unknown how this could happen.
      if (is_object($value)) {
        if (method_exists($value, 'toArray')) {
          $value = $value->toArray();
        }
        else {
          $value = (array) $value;
        }
      }
    });
  }

}
