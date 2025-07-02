<?php

namespace Drupal\blazy\internals;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazySettings;
use Drupal\blazy\Media\BlazyImage;
use Drupal\blazy\Utility\Check;
use Drupal\blazy\Utility\CheckItem;

/**
 * Provides internal non-reusable blazy utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Settings {

  /**
   * Provides common content settings.
   */
  public static function contently(array &$settings): void {
    $blazies = $settings['blazies'];

    // Disable all lazy stuffs since we got a brick here.
    // @todo recheck any misses, and refine overlaps.
    $settings['media_switch'] = $settings['ratio'] = '';
    $blazies->set('is.unlazy', TRUE)
      ->set('lazy.html', FALSE)
      ->set('media.type', '')
      ->set('placeholder', [])
      ->set('switch', '')
      ->set('use.bg', FALSE)
      ->set('use.blur', FALSE)
      ->set('use.content', TRUE)
      ->set('use.loader', FALSE)
      ->set('use.player', FALSE);
  }

  /**
   * Returns the highest views rows, or field items count to determine gallery.
   *
   * Sliders may trick count 100 into just 2 for their magic chunk trick.
   */
  public static function count($blazies, $default = 0): int {
    $field = $blazies->get('total', 0) ?: $blazies->get('count', 0);
    $views = $blazies->get('view.count', 0);
    $count = $views > $field ? $views : $field;
    $total = $count > $default ? $count : $default;

    // Store it in an undisturbed location.
    $blazies->set('item.count', $total);
    return $total;
  }

  /**
   * Update count by delta option.
   */
  public static function updateCountByDelta(array &$settings): void {
    $blazies  = $settings['blazies'];
    $by_delta = $settings['by_delta'] ?? -1;
    $total    = $blazies->total();

    if ($by_delta > -1 && $by_delta < $total) {
      $settings['count'] = 1;
      $blazies->set('count', 1)
        ->set('total', 1)
        ->set('item.count_original', $total);
    }
  }

  /**
   * Returns minimal View data.
   */
  public static function getViewFieldData($view): array {
    $data = $names = [];
    foreach ($view->field as $field_name => $field) {
      if ($options = $field->options ?? []) {
        $names[] = $field_name;
        $subsets = $options['settings'] ?? [];
        $type = $options['type'] ?? 'x';

        if ($subsets) {
          if (isset($subsets['media_switch'])) {
            $data['formatters'][] = [
              'type' => $type,
              'field_name' => $field_name,
              'settings' => $subsets,
            ];
          }

          if (!empty($options['group_rows'])
            && $limit = $options['delta_limit'] ?? 0) {
            // Ensures we are in the ecosystem. Grid option is only available at
            // multi-value fields. A single value is not a concern.
            if (isset($subsets['grid_medium'])) {
              $data[$field_name]['limit'] = $limit;
              $data[$field_name]['offset'] = $options['delta_offset'] ?? 0;
              $data[$field_name]['options'] = $options;
            }
          }
        }
      }
    }

    $data['fields'] = $names;
    return $data;
  }

  /**
   * Returns delta_limit option.
   */
  public static function getViewLimit($blazies): int {
    $data = $blazies->get('view.data', []);
    $name = $blazies->get('field.name', 'x');
    return $data[$name]['limit'] ?? 0;
  }

  /**
   * Alias for BlazySettings().
   */
  public static function init(array $data = []): BlazySettings {
    return new BlazySettings($data);
  }

  /**
   * Disable lazyload as required.
   *
   * The following will disable lazyload:
   * - if loading: slider (LCP) is chosen for initial slide, normally delta 0.
   * - If unlazy: globally disabled via `No JavaScript` option.
   * - If static: CK Editor/ preview mode, AMP, and sandboxed mode.
   */
  public static function isUnlazy($blazies): bool {
    return $blazies->is('unlazy')
      || $blazies->is('static')
      || $blazies->is('slider') && $blazies->is('initial');
  }

  /**
   * Prepares the essential settings, URI, delta, cache , etc.
   */
  public static function prepare(array &$settings, $item, $called = FALSE): void {
    CheckItem::essentials($settings, $item, $called);
    CheckItem::insanity($settings);
  }

  /**
   * Blazy is prepared with an URI.
   */
  public static function prepared(array &$settings, $item): void {
    BlazyImage::prepare($settings, $item);
  }

  /**
   * Preserves crucial blazy specific settings to avoid accidental overrides.
   *
   * To pass the first found Blazy formatter cherry settings into the container,
   * like Blazy Grid which lacks of options like `Media switch` or lightboxes,
   * so that when this is called at the container level, it can populate
   * lightbox gallery attributes if so configured.
   * This way at Views style, the container can have lightbox galleries without
   * extra settings, as long as `Use field template` is disabled under
   * `Style settings`, otherwise flattened out as a string.
   *
   * @see \Drupa\blazy\BlazyManagerBase::isBlazy()
   */
  public static function preserve(array &$parentsets, array &$childsets): void {
    self::verify($parentsets);
    self::verify($childsets);

    // @todo add more formatter related settings where Views styles have none.
    $cherries = BlazyDefault::cherrySettings();

    foreach ($cherries as $key => $value) {
      $fallback = $parentsets[$key] ?? $value;
      // Ensures to respect parent formatter, or Views style if provided.
      $parentsets[$key] = isset($childsets[$key]) && empty($fallback)
        ? $childsets[$key]
        : $fallback;
    }

    $parent = $parentsets['blazies'];
    $child  = $childsets['blazies'];

    if ($bg = $parentsets['background'] ?? FALSE) {
      $parent->set('is.bg', $bg);
    }

    // $parent->set('first.settings', array_filter($child));
    // $parent->set('first.item_id', $child->get('item.id'));
    // Hints containers to build relevant lightbox gallery attributes.
    $childbox  = $child->get('lightbox.name');
    $parentbox = $parent->get('lightbox.name');

    // Ensures to respect parent formatter or Views style if provided.
    // The moral of this method is only if parent lacks of settings like Grid.
    // Other settings are not parents' business. Only concerns about those
    // needed by the container, e.g. LIGHTBOX for [data-LIGHTBOX-gallery].
    if ($childbox && !$parentbox) {
      // @todo use Check::lightboxes($settings);
      $optionset = $child->get('lightbox.optionset', $childbox) ?: $childbox;
      $parent->set('lightbox.name', $childbox)
        ->set($childbox, $optionset)
        ->set('is.lightbox', TRUE)
        ->set('switch', $child->get('switch'));

      // Now that we got a child lightbox, overrides parent for sure.
      $parentsets['media_switch'] = $childbox;
    }

    $parent->set('first', $child->get('first', []), TRUE)
      ->set('was.preserve', TRUE);
  }

  /**
   * Preliminary settings, normally at container/ global level.
   *
   * @todo refine to separate container from item level. At least move grid out.
   */
  public static function preSettings(array &$settings, $root = TRUE): void {
    $blazies = self::verify($settings);

    // Checks for basic features, here for both formatters and views fields.
    // To detect available media bundles from views field when
    // BlazyEntity::prepare() was called too early before media data set.
    // @todo move it back after initialized after both are synced.
    Check::container($settings);

    if ($blazies->was('initialized')) {
      return;
    }

    // Checks for lightboxes.
    Check::lightboxes($settings);

    // Checks for grids.
    if ($root) {
      Check::grids($settings);
    }

    // Checks for Image styles, excluding Responsive image.
    BlazyImage::styles($settings);

    // Marks it processed.
    $blazies->set('was.initialized', TRUE);
  }

  /**
   * Modifies the common UI settings inherited down to each item.
   */
  public static function postSettings(array &$settings): void {
    // Failsafe, might be called directly at ::attach() outside the workflow.
    $blazies = self::verify($settings);
    if (!$blazies->was('initialized')) {
      self::preSettings($settings);
    }
  }

  /**
   * Reset the BlazySettings per item to have unique URI, delta, style, etc.
   */
  public static function reset(array &$settings, $key = 'blazies', array $defaults = []): BlazySettings {
    // Other implementors should verify the $key prior to calling this.
    self::verify($settings, $key, $defaults);

    // The settings instance must be unique per item.
    $config = &$settings[$key];
    if (!$config->was('reset')) {
      $config->reset($settings, $key);
      $config->set('was.reset', TRUE);
    }

    return $config;
  }

  /**
   * A helper to gradually convert things to #things to avoid render error.
   */
  public static function hashtag(array &$data, $key = 'settings', $unset = FALSE): void {
    if (!isset($data["#$key"])) {
      $data["#$key"] = $data[$key] ?? [];
    }

    // Temporary failsafe.
    if ($unset) {
      unset($data[$key]);
    }

    $blazy = "#blazy";
    if ($key == 'settings' && isset($data[$blazy])) {
      $data["#$key"] = $data[$blazy];

      // Temporary failsafe.
      if ($unset) {
        unset($data[$blazy]);
      }
    }
  }

  /**
   * A helper to gradually convert things to #things to avoid render error.
   */
  public static function toHashtag(array $data, $key = 'settings', $default = []) {
    $result = $data["#$key"] ?? $data[$key] ?? $default;
    if (!$result && $key == 'settings') {
      $result = $data["#blazy"] ?? $default;
    }
    return $result;
  }

  /**
   * Sets a token based on media or image url.
   */
  public static function tokenize($blazies): void {
    $url = $blazies->get('media.embed_url') ?: $blazies->get('image.url');
    $uri = $blazies->get('image.uri');
    $token = substr(md5($uri . $url), 0, 11);

    self::scriptable($blazies);

    $blazies->set('media.token', 'b-' . $token);
  }

  /**
   * Verify `blazies` exists, in case accessed outside the workflow.
   */
  public static function verify(
    array &$settings,
    $key = 'blazies',
    array $defaults = [],
  ): BlazySettings {
    if (!isset($settings[$key])) {
      $settings += $defaults ?: Blazy::init();

      // A failsafe for edge cases:
      if (!isset($settings[$key])) {
        $settings[$key] = self::init();
      }
    }

    // In case overriden above without extending self::init().
    $settings += Blazy::init();
    return $settings[$key];
  }

  /**
   * Sets Instagram script if so configured, for oembed:instagram, not VEF.
   */
  private static function scriptable($blazies): void {
    if (!$blazies->is('iframeable')) {
      if ($blazies->is('instagram') && $blazies->is('instagram_api')) {
        $blazies->set('use.instagram_api', TRUE)
          ->set('use.scripted_iframe', $blazies->use('iframe'));
      }
    }
  }

}
