<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;

/**
 * A Trait common for all blazy, including its sub-modules, text formatters.
 *
 * By-passed routines at BlazyFormatter designed for Image, Media, entities.
 * Bp-passed theme_[blazy|slick|splide|gridstack|mason|etc.]() routines for
 * more relevant themes/ types like processed_text, or others.
 */
trait BlazyFormatterViewBaseTrait {

  /**
   * Returns base view elements.
   */
  protected function baseViewElements(
    FieldItemListInterface $items,
    $langcode,
    array $settings = [],
  ): array {
    // Early opt-out if the field is empty.
    if ($items->isEmpty()) {
      return [];
    }

    // Collects specific settings to this formatter.
    $defaults = $this->buildSettings();
    $settings = $this->formatter->merge($settings, $defaults);

    // Internal overrides before enough data is populated below.
    $this->preSettings($settings, $langcode);

    // BlazyFormatter::buildSettings() contains media, irrelevant for texts.
    // @todo move it into ::minimalSettings().
    $this->formatter->fieldSettings($settings, $items);

    // Ensures grids are respected in the least.
    $this->formatter->minimalSettings($settings, $items);

    // Internal overrides after enough data is populated above.
    $this->postSettings($settings, $langcode);

    // Build the settings.
    $build = ['#settings' => $settings, '#langcode' => $langcode];

    // Build the elements, and satisfy phpstan.
    if (method_exists($this, 'buildElements')) {
      // @todo remove $langcode at 3.x:
      $this->buildElements($build, $items, $langcode);
    }

    // Pass to manager for easy updates to all ecosystem formatters.
    $output   = $this->manager->build($build);
    $settings = $this->manager->toHashtag($build);

    // Return without field markup, if not so configured, else field.html.twig.
    return empty($settings['use_theme_field']) ? $output : [$output];
  }

  /**
   * Prepare the settings, allows sub-modules to re-use and override.
   */
  protected function preSettings(array &$settings, $langcode): void {
    $blazies = $settings['blazies'];
    $blazies->set('language.code', $langcode);
  }

  /**
   * Overrides the settings, allows sub-modules to re-use and override.
   */
  protected function postSettings(array &$settings, $langcode): void {
    // Do nothing.
  }

}
