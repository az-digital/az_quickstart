<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * A Trait common for all blazy, including its sub-modules, formatters.
 *
 * Since 2.9 this can replace and remove sub-module FormatterViewTrait anytime
 * for Media or Entity related formatters. For basic texts, use
 * self::baseViewElements() instead to by-pass
 * theme_[blazy|slick|splide|gridstack|mason|etc.]() routines.
 */
trait BlazyFormatterViewTrait {

  // Import once for very minimal difference.
  use BlazyFormatterViewBaseTrait;

  /**
   * Returns similar view elements across sub-modules.
   */
  protected function commonViewElements(
    FieldItemListInterface $items,
    $langcode,
    array $entities = [],
    array $settings = [],
  ) {
    // Modifies elements before building elements.
    $entities = empty($entities) ? [] : array_values($entities);
    $elements = $entities ?: $items;

    // Early opt-out if the field is empty, and also entities are empty.
    // Entities might not be empty even if items are when defaults are provided.
    // Specific to file, media, entity_reference, this was checked upstream.
    // Only needed during transition to Blazy:3.x for sub-modules BC.
    // This can be removed when sub-modules have all extended Blazy view at 3.x.
    /* @phpstan-ignore-next-line */
    if (empty($elements)) {
      return [];
    }

    // Collects specific settings to this formatter.
    $defaults = $this->buildSettings();
    $settings = $this->formatter->merge($settings, $defaults);

    // Internal overrides before enough data is populated below.
    $this->preSettings($settings, $langcode);

    // Build the settings.
    $build = ['#settings' => $settings, '#langcode' => $langcode];

    // Modifies settings before building elements.
    $this->formatter->preElements($build, $items, $entities);

    // Internal overrides after enough data is populated above.
    $this->postSettings($build['#settings'], $langcode);

    // BC hook_alters upstream are happy, ensures no more leaks downstream.
    // @todo recheck if any misses downstream.
    unset($build['settings']);

    // Build the elements.
    if (method_exists($this, 'buildElements')) {
      // @todo remove $langcode at 3.x:
      $this->buildElements($build, $elements, $langcode);
    }

    // Modifies settings post building elements.
    $this->formatter->postBuildElements($build, $items, $entities);

    // Pass to manager for easy updates to all ecosystem formatters.
    $output   = $this->manager->build($build);
    $settings = $this->manager->toHashtag($build);

    // Return without field markup, if not so configured, else field.html.twig.
    // @fixme this no longer works as expected since D9.5.10-D10.
    return empty($settings['use_theme_field']) ? $output : [$output];
  }

}
