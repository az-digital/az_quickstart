<?php

namespace Drupal\blazy;

use Drupal\blazy\Media\Thumbnail;
use Drupal\blazy\Utility\Check;
use Drupal\blazy\Utility\CheckItem;
use Drupal\blazy\Utility\Path;
use Drupal\blazy\internals\Internals;

/**
 * Provides common shared methods across Blazy ecosystem to DRY.
 */
abstract class BlazyManagerBase extends BlazyBase implements BlazyManagerBaseInterface {

  /**
   * {@inheritdoc}
   */
  public function attach(array $attach = []): array {
    $load    = $this->libraries->attach($attach);
    $blazies = $attach['blazies'];

    Internals::count($blazies);
    $this->attachments($load, $attach, $blazies);

    // Since 2.17 with self::attachments(), allows altering the ecosystem once.
    $this->moduleHandler->alter('blazy_attach', $load, $attach, $blazies);

    // No blazy libraries are loaded when `No JavaScript`, etc. enabled.
    if (isset($load['library'])) {
      $load['library'] = array_unique($load['library']);
    }
    return $load;
  }

  /**
   * {@inheritdoc}
   */
  public function containerAttributes(array &$attributes, array $settings): void {
    Blazy::containerAttributes($attributes, $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function getIoSettings(array $attach = []): object {
    return $this->libraries->getIoSettings($attach);
  }

  /**
   * {@inheritdoc}
   */
  public function getImageEffects(): array {
    $cid = 'blazy_image_effects';
    $effects[] = 'blur';
    return $this->getCachedOptions($cid, $effects);
  }

  /**
   * {@inheritdoc}
   */
  public function imageStyles(array &$settings, $multiple = FALSE, array $styles = []): void {
    $blazies = $settings['blazies'];
    $styles  = $styles ?: BlazyDefault::imageStyles();

    foreach ($styles as $key) {
      if (!$blazies->get($key . '.style') || $multiple) {
        if ($_style = ($settings[$key . '_style'] ?? '')) {
          if ($entity = $this->load($_style, 'image_style')) {
            $blazies->set($key . '.style', $entity)
              ->set($key . '.id', $entity->id());
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLightboxes(): array {
    $cid  = 'blazy_lightboxes';
    $data = $this->libraries->getLightboxes();

    return $this->getCachedOptions($cid, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function getStyles(): array {
    $styles = [
      'column' => 'CSS3 Columns',
      'grid' => 'Grid Foundation',
      'flexbox' => 'Flexbox',
      'flex' => 'Flexbox Masonry',
      'nativegrid' => 'Native Grid',
    ];
    $this->moduleHandler->alter('blazy_styles', $styles);
    return $styles;
  }

  /**
   * {@inheritdoc}
   */
  public function getThumbnail(array $settings, $item = NULL, array $captions = []): array {
    return Thumbnail::view($settings, $item, $captions);
  }

  /**
   * {@inheritdoc}
   */
  public function isBlazy(array &$settings, array $data = []): void {
    $original = $data;
    Check::blazyOrNot($settings, $data);

    // Allows lightboxes to inject options into `data-LIGHTBOX` attribute
    // at any blazy/ sub-modules containers using:
    // $blazies->set('data.LIGHTBOX_NAME', $options) only if needed.
    $this->moduleHandler->alter('blazy_is_blazy', $settings, $original);
  }

  /**
   * {@inheritdoc}
   */
  public function preBlazy(array &$build, $item = NULL): BlazySettings {
    $this->hashtag($build);
    $settings = &$build['#settings'];

    $this->verifySafely($settings);

    // Prevents double checks.
    // BlazySettings is a self containing object, initialized at container level
    // and must be renewed at item level to get correct delta, see #3278525.
    $blazies = $settings['blazies']->reset($settings);
    $delta   = $blazies->get('delta', $build['#delta'] ?? 0);
    $style   = $settings['image_style'] ?? NULL;

    // Workflows might be by-passed such as passing core Image formatter, not
    // Blazy for the main image displays within carousels, etc.
    if ($style && !$blazies->get('image.id')) {
      $this->imageStyles($settings);
    }

    $blazies->set('delta', $delta)
      ->set('is.api', TRUE);

    $this->moduleHandler->alter('blazy_preblazy', $settings, $build);

    CheckItem::essentials($settings, $item);
    return $settings['blazies'] ?? $blazies;
  }

  /**
   * {@inheritdoc}
   */
  public function postBlazy(array &$build, array $blazy): void {
    $item_build = $blazy['#build'] ?? [];

    // Update with blazy processed settings: unstyled extensions, SVG, etc.
    if ($blazysets = $this->toHashtag($item_build)) {
      $build['#settings']['blazies']->merge($blazysets['blazies']->storage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareData(array &$build): void {
    // Do nothing, let extenders share data at ease as needed.
  }

  /**
   * {@inheritdoc}
   */
  public function preSettings(array &$settings): void {
    $blazies = $this->verifySafely($settings);
    $ui = $this->config();
    $iframe_domain = $this->config('iframe_domain', 'media.settings');
    $is_debug = !$this->config('css.preprocess', 'system.performance');
    $ui['fx'] = $settings['fx'] ?? $ui['fx'] ?? '';
    $ui['blur_minwidth'] = (int) ($ui['blur_minwidth'] ?? 0);
    $fx = $settings['_fx'] ?? $ui['fx'];
    $fx = $blazies->get('fx', $fx);
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $lightboxes = $this->getLightboxes();
    $lightboxes = $blazies->get('lightbox.plugins', $lightboxes);
    $is_blur = $fx == 'blur';
    $is_resimage = $this->moduleExists('responsive_image');
    $namespace = $blazies->get('namespace');
    $use_blazy = TRUE;

    $blazies->set('fx', $fx)
      ->set('iframe_domain', $iframe_domain)
      ->set('is.debug', $is_debug)
      ->set('is.resimage', $is_resimage)
      ->set('is.unblazy', $this->config('io.unblazy'))
      ->set('language.current', $language)
      ->set('libs.animate', $fx)
      ->set('libs.blur', $is_blur)
      ->set('lightbox.plugins', $lightboxes)
      ->set('ui', $ui)
      ->set('use.blur', $is_blur)
      ->set('use.data_b', TRUE)
      ->set('use.theme_blazy', $use_blazy)
      ->set('use.theme_thumbnail', $use_blazy)
      ->set('version.blazy', Blazy::version('blazy'));

    if ($namespace && $namespace != 'blazy') {
      if ($this->moduleExists($namespace)) {
        $blazies->set('version.' . $namespace, Blazy::version($namespace));
      }
    }

    if ($router = Path::routeMatch()) {
      $route_name = $router->getRouteName();
      $blazies->set('route_name', $route_name);

      // @todo figure out more admin pages with AJAX where Blazy may sit.
      if (strpos($route_name, 'layout_builder.') !== FALSE) {
        $blazies->set('use.ajax', TRUE);
      }
    }

    // Sub-modules may need to provide their data to be consumed here.
    // Basicaly needs basic UI and definitions above to supply data properly,
    // such as to determine Slick/ Splide own lazy load methods based on UI.
    $this->preSettingsData($settings);

    // Preliminary globals when using the provided API.
    Internals::preSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public function postSettings(array &$settings): void {
    Internals::postSettings($settings);

    // Sub-modules may need to override Blazy definitions.
    $this->postSettingsData($settings);
  }

  /**
   * Overrides data massaged by [blazy|slick|splide, etc.]_settings_alter().
   */
  public function postSettingsAlter(array &$settings, $entity = NULL): void {
    Check::settingsAlter($settings, $entity);
  }

  /**
   * {@inheritdoc}
   */
  public function thirdPartyFormatters(): array {
    $formatters = ['file_audio', 'file_video'];
    $this->moduleHandler->alter('blazy_third_party_formatters', $formatters);
    return array_unique($formatters);
  }

  /**
   * {@inheritdoc}
   */
  public function toBlazy(array &$data, array &$captions, $delta): void {
    // Do nothing for sub-modules to use.
  }

  /**
   * {@inheritdoc}
   */
  public function setAttachments(
    array &$element,
    array $settings,
    array $attachments = [],
  ): void {
    $cache                 = $this->getCacheMetadata($settings);
    $attached              = $this->attach($settings);
    $attachments           = $this->merge($attached, $attachments);
    $element['#attached']  = $this->merge($attachments, $element, '#attached');
    $element['#cache']     = $this->merge($cache, $element, '#cache');
    $element['#namespace'] = static::$namespace;

    $this->moduleHandler->alter('blazy_element', $element, $settings);
  }

  /**
   * Provides data to be consumed by Blazy::preSettings().
   *
   * Such as to provide lazy attribute and class for Slick or Splide, etc.
   */
  protected function preSettingsData(array &$settings): void {
    // Do nothing, let extenders input data at ease as needed.
  }

  /**
   * Overrides data massaged by Blazy::postSettings().
   */
  protected function postSettingsData(array &$settings): void {
    // Do nothing, let extenders override data at ease as needed.
  }

}
