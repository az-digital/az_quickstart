<?php

namespace Drupal\blazy\Config\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\blazy\Utility\Arrays;

/**
 * Defines the common configuration entity.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
abstract class BlazyConfigEntityBase extends ConfigEntityBase implements BlazyConfigEntityBaseInterface {

  /**
   * The legacy CTools ID for the configurable optionset.
   *
   * @var string
   */
  protected $name;

  /**
   * The human-readable name for the optionset.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight to re-arrange the order of slick optionsets.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The plugin instance options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    $default = self::load('default');
    $default_options = $default ? $default->options : [];
    $options = Arrays::merge($this->options, $default_options);

    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($options, (array) $group);
      }
      elseif ($property && isset($options[$group])) {
        return $options[$group][$property] ?? NULL;
      }
      return $options[$group] ?? NULL;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options, $merged = TRUE): self {
    $this->options = $merged ? Arrays::merge($options, $this->options) : $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($group, $default = NULL) {
    // Makes sure to not call ::getOptions($group), else everything is dumped.
    return $this->getOptions()[$group] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($group, $value): self {
    if ($group == 'settings') {
      $value = array_merge(($this->options[$group] ?? []), $value);
    }

    $this->options[$group] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($ansich = FALSE): array {
    $settings = $this->options['settings'] ?? [];
    if ($ansich && $settings) {
      return $settings;
    }

    // With the Optimized options, all defaults are cleaned out, merge em.
    return $settings + self::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $values, $merged = TRUE): self {
    $settings = $this->options['settings'] ?? [];
    $this->options['settings'] = $merged
      ? array_merge($settings, $values)
      : $values;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    return $this->getSettings()[$name] ?? $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value): self {
    $this->options['settings'][$name] = $value;
    return $this;
  }

  /**
   * Returns available default options under group 'settings'.
   *
   * @param string $group
   *   The name of group: settings, responsives.
   *
   * @return array
   *   The default settings under options.
   */
  public static function defaultSettings($group = 'settings'): array {
    return self::load('default')->options[$group] ?? [];
  }

  /**
   * Load the optionset with a fallback.
   *
   * @param string $name
   *   The optionset name.
   *
   * @return object
   *   The optionset object.
   */
  public static function loadSafely($name) {
    $optionset = self::load($name);

    // Ensures deleted optionset while being used doesn't screw up.
    return $optionset ?: self::load('default');
  }

  /**
   * If optionset does not exist, load one.
   *
   * @param array $build
   *   The array containing normally settings, optionset, items, etc.
   * @param string $name
   *   The optionset name.
   *
   * @return object
   *   The optionset object.
   */
  public static function verifyOptionset(array &$build, $name) {
    // The element is normally present at template_preprocess, not builders.
    $key = isset($build['element']) ? 'optionset' : '#optionset';
    if (empty($build[$key])) {
      $build[$key] = self::loadSafely($name);
    }
    // Also returns it for convenient.
    return $build[$key];
  }

  /**
   * Load the optionset with a fallback.
   *
   * @param string $id
   *   The optionset name.
   *
   * @return object
   *   The optionset object.
   *
   * @todo deprecated in blazy:8.x-2.17 and is removed from blazy:3.0.0. Use
   *   self::loadSafely() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function loadWithFallback($id) {
    return self::loadSafely($id);
  }

}
