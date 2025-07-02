<?php

namespace Drupal\blazy_layout;

use Drupal\blazy\BlazyDefault;
use Drupal\blazy\BlazyManager;
use Drupal\blazy\Utility\Arrays;
use Drupal\blazy_layout\BlazyLayoutDefault as Defaults;

/**
 * Provides BlazyLayoutManager utility.
 */
class BlazyLayoutManager extends BlazyManager implements BlazyLayoutManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'box';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'blazy';

  /**
   * {@inheritdoc}
   */
  protected static $captionId = 'blazy';

  /**
   * {@inheritdoc}
   */
  public function getClasses(array $settings): array {
    if ($classes = $settings['classes'] ?? '') {
      $classes = array_map(
        '\Drupal\Component\Utility\Html::cleanCssIdentifier',
        explode(' ', $classes)
      );
      return array_filter($classes);
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(array $elements): array {
    return array_keys(
      array_filter(
        $elements,
        fn($k) => strpos($k, '#') === FALSE,
        ARRAY_FILTER_USE_KEY
      )
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions($count = NULL): array {
    $regions = [];
    $count = $count ?: Defaults::REGION_COUNT;

    foreach (range(1, $count) as $delta => $value) {
      $id    = Defaults::regionId($delta);
      $label = Defaults::regionLabel($delta);

      $regions[$id]['id']    = $id;
      $regions[$id]['delta'] = $delta;
      $regions[$id]['label'] = Defaults::regionTranslatableLabel($label);
      $regions[$id]['name']  = $id;
    }

    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function layoutSettings(array $settings, $count): array {
    $settings['blazy_layout'] = TRUE;
    $settings['ete'] = FALSE;

    if ($layouts = $settings['styles']['layouts'] ?? []) {
      $settings['ete'] = !empty($layouts['ete']);
      $settings['gapless'] = !empty($layouts['gapless']);
    }

    $this->verifySafely($settings);
    $this->preSettings($settings);

    $settings = $this->toSettings($settings);
    $blazies  = $settings['blazies'];

    $blazies->set('namespace', static::$namespace)
      ->set('is.grid', TRUE)
      ->set('is.lb', TRUE)
      ->set('grid.unlist', TRUE)
      ->set('grid.items', $settings['regions'] ?? [])
      ->set('item.id', static::$itemId)
      ->set('item.prefix', static::$itemPrefix)
      ->set('item.caption', static::$captionId)
      ->set('count', $count);

    $this->postSettings($settings);

    $settings = array_diff_key($settings, BlazyDefault::imageSettings());
    $settings = Arrays::filter($settings);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function parseClasses(array &$output, array $settings): void {
    if ($classes = $this->getClasses($settings)) {
      foreach ($classes as $class) {
        $output['#attributes']['class'][] = $class;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function selector($key, $region, array $options = []): string {
    $empty    = $options['empty'] ?? FALSE;
    $block_bg = $options['block_bg'] ?? FALSE;
    $prefix   = '.region';

    if ($region) {
      $region = str_replace('_', '-', $region);
      $prefix = ".region--{$region}";
    }

    if ($region == 'bg' && !in_array($key, ['background', 'overlay'])) {
      $prefix = '.region';
    }

    switch ($key) {
      case 'padding':
        return $region == 'bg' ? '' : $prefix;

      case 'background':
        return $empty || !$block_bg ? "{$prefix}, {$prefix} .b-bg" : "{$prefix} .b-bg";

      case 'overlay':
        return "{$prefix} .media__overlay";

      case 'text':
        return "{$prefix} p";

      case 'heading':
        return "{$prefix} h2, {$prefix} h3, {$prefix} .field__label";

      case 'link':
        return "{$prefix} a";

      case 'link_hover':
        return "{$prefix} a:hover";

      default:
        return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function toRules(array $data, $id): string {
    return implode(' ', array_map(
      function ($value, $key) use ($id) {
        if (strpos($value, 'ROOT') !== FALSE) {
          return str_replace('ROOT', ".blazy.b-layout.{$id}", $value);
        }

        if (strpos($key, ',') !== FALSE) {
          $vals = array_map('trim', explode(',', $key));
          $keys = [];
          foreach ($vals as $val) {
            $keys[] = ".blazy.{$id} {$val}";
          }

          $key = implode(', ', $keys);
          return "{$key} {{$value}}";
        }

        return $id == $key
          ? ".blazy.b-layout.{$key} {{$value}}"
          : ".blazy.{$id} {$key} {{$value}}";
      },
      $data,
      array_keys($data)
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaLibraries(): array {
    $libraries = [];
    $admin_theme = $this->config('admin', 'system.theme');

    // @todo remove once media_library is loaded at frontend modal.
    if ($this->moduleExists('media_library')) {
      $libraries[] = 'media_library/view';
      $libraries[] = 'media_library/ui';
      $libraries[] = 'media_library/widget';
    }

    if ($admin_theme == 'claro') {
      $libraries[] = 'claro/media_library.theme';
      $libraries[] = 'claro/media_library.ui';
    }
    elseif ($admin_theme == 'gin') {
      $libraries[] = 'gin/media_library';
    }

    // Adminimal, Classy has no special media library theme, skip.
    return $libraries;
  }

}
