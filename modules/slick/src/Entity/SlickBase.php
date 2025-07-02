<?php

namespace Drupal\slick\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Slick configuration entity.
 *
 * @todo extends BlazyConfigEntityBase post blazy:2.17.
 */
abstract class SlickBase extends ConfigEntityBase implements SlickBaseInterface {

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
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($this->options, (array) $group);
      }
      elseif (isset($property) && isset($this->options[$group])) {
        return $this->options[$group][$property] ?? NULL;
      }
      return $this->options[$group] ?? NULL;
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings($ansich = FALSE) {
    $settings = $this->options['settings'] ?? [];
    if ($ansich) {
      return $settings;
    }

    // With the Optimized options, all defaults are cleaned out, merge em.
    return $settings + self::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings = []) {
    $this->options['settings'] = $settings;
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
  public function setSetting($name, $value) {
    $this->options['settings'][$name] = $value;
    return $this;
  }

  /**
   * Returns available slick default options under group 'settings'.
   *
   * @param string $group
   *   The name of group: settings, responsives.
   *
   * @return array
   *   The default settings under options.
   */
  public static function defaultSettings($group = 'settings'): array {
    $settings = self::load('default')->options[$group] ?? [];
    self::removeUnsupportedSettings($settings);
    return $settings;
  }

  /**
   * Load the optionset with a fallback.
   *
   * @param string $id
   *   The optionset name.
   *
   * @return object
   *   The optionset object.
   */
  public static function loadSafely($id) {
    $optionset = self::load($id);

    // Ensures deleted optionset while being used doesn't screw up.
    return empty($optionset) ? self::load('default') : $optionset;
  }

  /**
   * Remove settings that aren't supported by the active library.
   */
  public static function removeUnsupportedSettings(array &$settings = []) {
    $library = \Drupal::config('slick.settings')->get('library');
    // The `focusOnSelect`is required to sync asNavFor, but removed. Here must
    // be kept for future fix, or less breaking changes due to different logic.
    if ($library == 'accessible-slick') {
      unset($settings['accessibility']);
      unset($settings['focusOnChange']);
    }
    else {
      unset($settings['regionLabel']);
      unset($settings['useGroupRole']);
      unset($settings['instructionsText']);
      unset($settings['useAutoplayToggleButton']);
      unset($settings['pauseIcon']);
      unset($settings['playIcon']);
      unset($settings['arrowsPlacement']);
    }
  }

  /**
   * If optionset does not exist, create one.
   *
   * @param array $build
   *   The build array.
   * @param string $name
   *   The optionset name.
   *
   * @return \Drupal\slick\Entity\Slick
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
   * @todo deprecated in slick:8.x-2.10 and is removed from slick:3.0.0.
   *   Use self::loadSafely() instead.
   * @see https://www.drupal.org/node/3103018
   */
  public static function loadWithFallback($id) {
    return self::loadSafely($id);
  }

}
