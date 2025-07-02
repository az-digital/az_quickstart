<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Config\StorableConfigBase;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * The config sorter service core should have had.
 *
 * @internal This is not an API, anything here might change without notice. Use config_normalizer 2.x instead.
 */
class ConfigSorter {

  /**
   * The typed config manager to get the schema from.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * The active storage to help with the sorting.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $active;

  /**
   * ConfigCaster constructor.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager to look up the schema.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active storage to help with the sorting.
   */
  public function __construct(TypedConfigManagerInterface $typedConfigManager, StorageInterface $active) {
    $this->typedConfigManager = $typedConfigManager;
    $this->active = $active;
  }

  /**
   * Cast and sort the config data in a normalized way depending on its schema.
   *
   * @param string $name
   *   The config name.
   * @param array $data
   *   The config data.
   *
   * @return array
   *   The cast and sorted data.
   */
  public function sort(string $name, array $data): array {
    // The sorter is an object extending from the core config class but doing
    // the casting and sorting only.
    // This is an anonymous class so that we are sure each object gets used only
    // once and nobody uses it for anything else. We extend the core class so
    // that we can access the methods and inherit the improvements made to it.
    $sorter = new class($this->typedConfigManager) extends StorableConfigBase {

      /**
       * Sort the config.
       *
       * @param string $name
       *   The config name.
       * @param array $data
       *   The data.
       *
       * @return array
       *   The sorted array.
       */
      public function anonymousSort(string $name, array $data): array {
        // Set the object up.
        self::validateName($name);
        $this->validateKeys($data);
        $this->setName($name)->initWithData($data);

        // This is essentially what \Drupal\Core\Config\Config::save does when
        // there is untrusted data before persisting it and dispatching events.
        if ($this->typedConfigManager->hasConfigSchema($this->name)) {
          // Once https://www.drupal.org/project/drupal/issues/2852557 is fixed
          // we do just: $this->data = $this->castValue(NULL, $this->data);.
          foreach ($this->data as $key => $value) {
            $this->data[$key] = $this->castValue($key, $value);
          }
        }
        else {
          foreach ($this->data as $key => $value) {
            $this->validateValue($key, $value);
          }
        }

        // This should now produce the same data as if the config object had
        // been saved and loaded. So we can return it.
        return $this->data;
      }

      /**
       * The constructor for passing the TypedConfigManager.
       *
       * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
       *   The taped config manager.
       */
      public function __construct(TypedConfigManagerInterface $typedConfigManager) {
        $this->typedConfigManager = $typedConfigManager;
      }

      /**
       * {@inheritdoc}
       */
      public function save($has_trusted_data = FALSE) {
        throw new \LogicException();
      }

      /**
       * {@inheritdoc}
       */
      public function delete() {
        throw new \LogicException();
      }

    };

    // Sort the data using the core class we extended.
    $data = $sorter->anonymousSort($name, $data);

    // Sort dependencies. This is a special case, but one we know how to handle.
    // We may have to wait for Drupal 9.4 or later to sort sequences.
    if (isset($data['dependencies'])) {
      $mapping = ['config' => 0, 'content' => 1, 'module' => 2, 'theme' => 3, 'enforced' => 4];
      $dependency_sort = function (array $dependencies) use ($mapping) {
        // Only sort the keys that exist.
        $mapping_to_replace = array_intersect_key($mapping, $dependencies);
        $dependencies = array_replace($mapping_to_replace, $dependencies);
        foreach ($dependencies as $type => &$list) {
          // We know that dependencies are sorted by value.
          if ($type !== 'enforced') {
            sort($list);
          }
        }
        return $dependencies;
      };
      $data['dependencies'] = $dependency_sort($data['dependencies']);
      if (isset($data['dependencies']['enforced'])) {
        $data['dependencies']['enforced'] = $dependency_sort($data['dependencies']['enforced']);
      }
    }

    // Unfortunately Drupal core does not let one easily sort config.
    // Only when entities are saved some order is assured, for config objects
    // there is no sorting and both of these things can not easily be addressed.
    // @see https://www.drupal.org/project/drupal/issues/3230826
    if ($this->active->exists($name)) {
      // Since we are only concerned about sorting to prevent unnecessary diffs
      // we don't sort when the config doesn't exist in the active storage.
      $data = $this->sortDeep($data, $this->active->read($name));
    }

    return $data;
  }

  /**
   * Sort one array with the sorting order of another.
   *
   * @param array $config
   *   The array to sort.
   * @param array $model
   *   The array to get the sorting order from.
   *
   * @return array
   *   The sorted array.
   */
  protected function sortDeep(array $config, array $model): array {
    if ($config === $model) {
      // Shortcut.
      return $config;
    }
    $sorted = [];
    $common = array_intersect_key($model, $config);
    $unique = array_diff_key($config, $model);
    foreach ($common as $key => $modelValue) {
      $value = $config[$key];
      // We maybe need to differentiate between mappings and sequences, use the
      // config schema and all. But as long as core doesn't give us any help we
      // just sort in the most crude way to get the job done.
      if (is_array($modelValue) && is_array($value) && !empty($value)) {
        // Recurse into nested values.
        $value = $this->sortDeep($value, $modelValue);
      }
      // Fill the $sorted array in the same order as the model.
      $sorted[$key] = $value;
    }
    foreach ($unique as $key => $value) {
      // The values that do not exist in the model do not need to be sorted.
      $sorted[$key] = $value;
    }

    return $sorted;
  }

}
