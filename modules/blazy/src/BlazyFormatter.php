<?php

namespace Drupal\blazy;

use Drupal\blazy\Media\Preloader;
use Drupal\blazy\Utility\Check;

/**
 * Provides common image, file, media formatter-related methods.
 */
class BlazyFormatter extends BlazyManager implements BlazyFormatterInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'content';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  public function fieldSettings(array &$settings, $items): void {
    Check::fields($settings, $items);
  }

  /**
   * {@inheritdoc}
   *
   * @todo make it protected after sub-modules, mostly are just tests + BVEF.
   */
  public function buildSettings(array &$build, $items) {
    $this->hashtag($build);
    $settings = &$build['#settings'];

    // BC for mismatched minor versions.
    $entity = $items->getEntity();

    $build['#entity'] = $entity;
    $this->prepareData($build);
    $this->fieldSettings($settings, $items);

    // Minor byte saving.
    if (!empty($settings['caption'])) {
      $settings['caption'] = array_filter($settings['caption']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function minimalSettings(array &$settings, $items): void {
    Check::grids($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function preBuildElements(array &$build, $items, array $entities = []) {
    $this->hashtag($build);
    $settings = &$build['#settings'];

    // BC for mismatched minor versions.
    $blazies   = $this->verifySafely($settings);
    $plugin_id = $blazies->get('field.plugin_id');

    // BC for non-nego vanilla formatters identified by its vanilla plugin ID.
    if ($plugin_id && strpos($plugin_id, 'vanilla') !== FALSE) {
      $settings['vanilla'] = TRUE;
      $blazies->set('is.vanilla', TRUE);
    }

    // Extracts initial settings:
    // - Container or root level settings: lightboxes, grids, etc.
    // - Map (Responsive) image style option to its entity, etc.
    // - Lazy load decoupled via `No JavaScript: lazy`, etc.
    $this->preSettings($settings);

    // Extracts the first image item to build colorbox/zoom-like gallery.
    // Also prepare URIs for the new Preload option.
    // Requires image style entities from ::preSettings() above.
    Preloader::prepare($settings, $items, $entities);

    // Extracts (Responsive) image dimensions, requires first.uri above.
    $this->postSettings($settings);

    // Allows altering the presettings once for the entire ecosystem.
    // Has the needed settings above to modify sub-modules ::buildSettings().
    $this->moduleHandler->alter('blazy_presettings', $settings, $items, $entities);

    // Extended by sub-modules with data massaged above.
    $this->buildSettings($build, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function preElements(array &$build, $items, array $entities = []): void {
    $this->preBuildElements($build, $items, $entities);

    $settings = &$build['#settings'];

    $build['#vanilla'] = !empty($settings['vanilla']);

    // Since 2.17, allows altering the settings once for the entire ecosystem,
    // rather than each hook_alter for every modules.
    // The $build contains #settings, or potential #optionset for sub-modules.
    $this->moduleHandler->alter('blazy_settings', $build, $items, $entities);

    // Combines settings with the provided hook_alter().
    $this->postSettingsAlter($settings, $items->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public function postBuildElements(array &$build, $items, array $entities = []) {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];

    // The last method before being passed to each manager builders.
    // Supports lightbox gallery if using Blazy formatter.
    // Some formatter has a toggle Vanilla, only makes sense for non-vanilla.
    if (empty($settings['vanilla']) && isset($settings['image_style'])) {
      // Extract the first found formatter settings AFTER being processed by
      // blazy/ sub-module #pre_render so to inform the top level container
      // about at least the first found URI which is not available at
      // ::preElements() so to help ElevateZoomPlus, and others needing this
      // to dipslay their first preview. The most comprehensible sample is
      // Colorbox large display with small ones, similar to ElevateZoomPlus.
      if ($item = ($build['items'][0] ?? [])) {
        $fallback = $item[static::$itemId]['#build'] ?? [];
        $data = $item['#build'] ?? $fallback;

        if ($data = array_filter($data)) {
          if ($blazy = $data['#settings']['blazies'] ?? NULL) {
            $blazies->set('first.data', $data)
              ->set('first.uri', $blazy->get('image.uri'));
          }
        }
      }
    }
  }

}
