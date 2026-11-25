<?php

namespace Drupal\az_core\Utility;

/**
 * Renames Arizona Bootstrap brand color classes deprecated in 5.3.0.
 *
 * Arizona Bootstrap 5.3.0 renamed its brand colors to match the University's
 * design tokens (for example, Midnight is now Tinta) and kept the old class
 * names as deprecated aliases.
 *
 * @see https://github.com/az-digital/arizona-bootstrap/pull/2369
 */
final class AZBootstrapColorNames {

  /**
   * Deprecated brand color names, keyed by old name, with new names as values.
   *
   * Matches $deprecated-color-names in Arizona Bootstrap's
   * scss/custom/_variables.scss.
   *
   * @var array<string, string>
   */
  const DEPRECATED_COLOR_NAMES = [
    'midnight' => 'tinta',
    'oasis' => 'arroyo-blue',
    'sky' => 'rain',
    'chili' => 'sonoran-red',
    'bloom' => 'bougainvillea',
    'leaf' => 'saguaro',
    'river' => 'shade',
    'mesa' => 'brick',
    'cool-gray' => 'cloud',
    'warm-gray' => 'caliche',
  ];

  /**
   * Class prefixes that are combined with each deprecated color name.
   *
   * Matches $deprecated-color-class-prefixes in Arizona Bootstrap's
   * scss/deprecated/_colors.scss.
   *
   * @var string[]
   */
  const DEPRECATED_COLOR_CLASS_PREFIXES = [
    'bg',
    'bg-gradient',
    'bg-transparent',
    'border',
    'btn',
    'btn-outline',
    'callout',
    'focus-ring',
    'table',
    'text',
    'text-bg',
    'text-bg-gradient',
    'text-bg-transparent',
  ];

  /**
   * Other deprecated classes, keyed by old class, with new classes as values.
   *
   * @var array<string, string>
   */
  const DEPRECATED_CLASSES = [
    'bg-monsoon-sky-start' => 'bg-monsoon-rain-start',
    'bg-monsoon-sky-end' => 'bg-monsoon-rain-end',
    // Defined by az_ranking rather than Arizona Bootstrap.
    'ranking-text-midnight' => 'ranking-text-tinta',
  ];

  /**
   * Returns every deprecated class, keyed by old class.
   *
   * @return array<string, string>
   *   New classes, keyed by the deprecated classes they replace.
   */
  public static function getClassMap(): array {
    static $map;
    if (!isset($map)) {
      $map = self::DEPRECATED_CLASSES;
      foreach (self::DEPRECATED_COLOR_CLASS_PREFIXES as $prefix) {
        foreach (self::DEPRECATED_COLOR_NAMES as $old => $new) {
          $map[$prefix . '-' . $old] = $prefix . '-' . $new;
        }
      }
    }
    return $map;
  }

  /**
   * Replaces deprecated classes in a space-separated list of classes.
   *
   * @param string $classes
   *   A space-separated list of classes, such as 'btn w-100 btn-sky'.
   *
   * @return string
   *   The list with each deprecated class replaced by its new class. Other
   *   classes and whitespace are left as they are.
   */
  public static function updateClasses(string $classes): string {
    $map = self::getClassMap();
    return preg_replace_callback('/\S+/', static fn ($matches) => $map[$matches[0]] ?? $matches[0], $classes);
  }

}
