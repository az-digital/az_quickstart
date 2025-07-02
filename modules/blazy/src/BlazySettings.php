<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\NestedArray;
use Drupal\blazy\Utility\Arrays;

/**
 * Provides settings object.
 *
 * If you would like to pass this into Twig, be sure to call self::storage(),
 * e.g.: $variables['blazies'] = $blazies->storage(); This results in array
 * which works great with Twig dot notation.
 * Do not dump it directly as an object, e.g.: $variables['blazies'] = $blazies;
 * This may mangle methods of the same names as array keys due to how Twig dot
 * notation work. See shortcuts below. You can, but should use ugly `get`, e.g.:
 * blazies.get.is.awesome rather than blazies.is.awesome as otherwise
 * ArgumentCountError exception is thrown.
 */
class BlazySettings implements \Countable {

  /**
   * Stores the settings.
   *
   * @var \stdClass[]
   */
  protected $storage = [];

  /**
   * Creates a new BlazySettings instance.
   *
   * @param \stdClass[] $storage
   *   The storage.
   */
  public function __construct(array $storage = []) {
    $this->storage = $storage ? Arrays::filter($storage) : [];
  }

  /**
   * Counts total items, might be unreal, tweaked by slider grids.
   */
  public function count(): int {
    return $this->get('count', 0);
  }

  /**
   * Returns total items, the untweakable count.
   */
  public function total(): int {
    return $this->get('total', 0);
  }

  /**
   * Returns values from a key.
   *
   * @param string $key
   *   The storage key, if empty, similar to self::storage().
   * @param string $default_value
   *   The storage default_value.
   *
   * @return mixed
   *   A mixed value (array, string, bool, null, etc.).
   */
  public function get($key = NULL, $default_value = NULL) {
    if (empty($key)) {
      return $this->storage;
    }

    $parts = array_map('trim', explode('.', $key));
    if (count($parts) == 1) {
      return $this->storage[$key] ?? $default_value;
    }

    $value = NestedArray::getValue($this->storage, $parts, $key_exists);
    return $key_exists ? $value : $default_value;
  }

  /**
   * Returns a convenient shortcut to get a feature with a `data` key.
   *
   * @param string $key
   *   The storage key.
   * @param array $default_value
   *   The storage default_value.
   *
   * @return array
   *   The array of items inside the data key, or empty array.
   */
  public function data($key, array $default_value = []): array {
    return $this->get('data.' . $key, $default_value) ?: [];
  }

  /**
   * Returns a convenient shortcut to get a feature with a `filter` key.
   *
   * @param string $key
   *   The storage key.
   * @param string $default_value
   *   The storage default_value.
   * @param string $namespace
   *   The plugin namespace.
   *
   * @return mixed
   *   A mixed value (array, string, bool, null, etc.).
   */
  public function filter($key, $default_value = NULL, $namespace = 'blazy') {
    return $this->get('filter.' . $namespace . '.' . $key, $default_value);
  }

  /**
   * Returns a convenient shortcut to get a feature with an `form` key.
   *
   * @param string $key
   *   The storage key.
   * @param bool $default_value
   *   The storage default_value.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function form($key, $default_value = FALSE): bool {
    return $this->get('form.' . $key, $default_value) ?: FALSE;
  }

  /**
   * Returns a convenient shortcut to get a feature with an `is` key.
   *
   * @param string $key
   *   The storage key.
   * @param bool $default_value
   *   The storage default_value.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function is($key, $default_value = FALSE): bool {
    return $this->get('is.' . $key, $default_value) ?: FALSE;
  }

  /**
   * Returns a convenient shortcut to get a feature with a `no` key.
   *
   * @param string $key
   *   The storage key.
   * @param bool $default_value
   *   The storage default_value.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function no($key, $default_value = FALSE): bool {
    return $this->get('no.' . $key, $default_value) ?: FALSE;
  }

  /**
   * Returns a convenient shortcut to get a feature with a `was` key.
   *
   * To verify if the expected workflow is by-passed when the key was missing.
   *
   * @param string $key
   *   The storage key.
   * @param bool $default_value
   *   The storage default_value.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function was($key, $default_value = FALSE): bool {
    return $this->get('was.' . $key, $default_value) ?: FALSE;
  }

  /**
   * Returns a convenient shortcut to get a feature with a `use` key.
   *
   * @param string $key
   *   The storage key.
   * @param bool $default_value
   *   The storage default_value.
   *
   * @return bool
   *   Returns TRUE or FALSE.
   */
  public function use($key, $default_value = FALSE): bool {
    return $this->get('use.' . $key, $default_value) ?: FALSE;
  }

  /**
   * Returns a convenient shortcut to get a feature with a `ui` key.
   *
   * @param string $key
   *   The storage key.
   * @param string $default_value
   *   The storage default_value.
   *
   * @return mixed
   *   A mixed value (array, string, bool, null, etc.).
   */
  public function ui($key, $default_value = NULL) {
    return $this->get('ui.' . $key, $default_value);
  }

  /**
   * Sets values for a key.
   */
  public function set($key, $value = NULL, $merge = TRUE): self {
    if (is_array($key)) {
      // Ensures to merge to not nullify previous values.
      $merge = TRUE;
      foreach ($key as $k => $v) {
        $this->setInternal($k, $v, $merge);
      }
      return $this;
    }

    return $this->setInternal($key, $value, $merge);
  }

  /**
   * Merges data into a configuration object.
   *
   * @param array $data_to_merge
   *   An array containing data to merge.
   *
   * @return $this
   *   The configuration object.
   */
  public function merge(array $data_to_merge): self {
    // Preserve integer keys so that configuration keys are not changed.
    $this->setData(NestedArray::mergeDeepArray([$this->storage, $data_to_merge], TRUE));
    return $this;
  }

  /**
   * Provides an object from an array within the optional limited keys.
   *
   * @param array $data
   *   The data to be onverted into an object.
   * @param array $keys
   *   The optional limited keys.
   *
   * @return object
   *   The object.
   */
  public function objectify(array $data, array $keys = []): object {
    $item = new \stdClass();
    $keys = $keys ?: array_keys($data);
    foreach ($keys as $key) {
      if ($value = $data[$key] ?? NULL) {
        $item->{$key} = $value;
      }
    }
    return $item;
  }

  /**
   * Replaces the data of this configuration object.
   *
   * @param array $data
   *   The new configuration data.
   *
   * @return $this
   *   The configuration object.
   */
  public function setData(array $data): self {
    $this->storage = $data;
    return $this;
  }

  /**
   * Removes item from this.
   *
   * @param string $key
   *   The key to unset.
   *
   * @return $this
   *   The configuration object.
   */
  public function unset($key): self {
    $parts = array_map('trim', explode('.', $key));
    if (count($parts) == 1) {
      unset($this->storage[$key]);
    }
    else {
      NestedArray::unsetValue($this->storage, $parts);
    }
    return $this;
  }

  /**
   * Check if a config by its key exists.
   *
   * @param string $key
   *   The key to check.
   * @param string|object $group
   *   The BlazySettings as sub-key to check for, or a parent key string.
   *
   * @return bool
   *   True if found.
   */
  public function isset($key, $group = NULL): bool {
    $found = FALSE;
    $parts = array_map('trim', explode('.', $key));
    if (count($parts) == 1) {
      if ($group) {
        if (is_string($group)) {
          $found = isset($this->storage[$group][$key]);
        }
        elseif ($group instanceof BlazySettings) {
          $found = isset($group->storage()[$key]);
        }
      }
      else {
        $found = isset($this->storage[$key]);
      }
    }
    else {
      $found = NestedArray::keyExists($this->storage, $parts);
    }
    return $found;
  }

  /**
   * Reset or renew the BlazySettings object.
   *
   * Normally called at item level so to get correct delta or settings per item.
   *
   * @param array $settings
   *   The settings to reset/ renew the instance.
   * @param string $key
   *   The key identifying this reset object.
   *
   * @return \Drupal\blazy\BlazySettings
   *   The new BlazySettings instance.
   */
  public function reset(array &$settings, $key = 'blazies'): self {
    $data = $this->storage;

    // @todo re-check, or remove.
    // if ($data && $this->is('debug')) {
    // $this->rksort($data);
    // }
    $instance = new self($data);

    $settings[$key] = $instance;
    return $instance;
  }

  /**
   * Returns the whole array.
   */
  public function storage(): array {
    return $this->storage;
  }

  /**
   * Provides a fake image item object.
   *
   * @param array $data
   *   The data to be onverted into an object.
   *
   * @return object
   *   The object.
   *
   * @todo remove at 3.x when ImageItem is removed.
   */
  public function toImage(array $data): object {
    return $this->objectify($data, BlazyDefault::imageProperties());
  }

  /**
   * Sets values for a key.
   */
  private function setInternal($key, $value = NULL, $merge = TRUE): self {
    $parts = array_map('trim', explode('.', $key));

    if (is_array($value) && $merge) {
      $value = array_merge((array) $this->get($key, []), $value);
      // @todo recheck Array to string conversion.
      if (isset($value[1])) {
        $value = array_unique($value, SORT_REGULAR);
      }
    }

    if (count($parts) == 1) {
      $this->storage[$key] = $value;
    }
    else {
      NestedArray::setValue($this->storage, $parts, $value);
    }
    return $this;
  }

  /**
   * Sorts recursively.
   *
   * @phpstan-ignore-next-line
   */
  private function rksort(&$a): bool {
    if (!is_array($a)) {
      return FALSE;
    }

    ksort($a);
    foreach ($a as $k => $v) {
      $this->rksort($a[$k]);
    }
    return TRUE;
  }

}
