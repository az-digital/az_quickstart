<?php

namespace Drupal\slick;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerBase;
use Drupal\slick\Entity\Slick;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides slick manager.
 */
class SlickManager extends BlazyManagerBase implements SlickManagerInterface {

  /**
   * {@inheritdoc}
   */
  protected static $namespace = 'slick';

  /**
   * {@inheritdoc}
   */
  protected static $itemId = 'slide';

  /**
   * {@inheritdoc}
   */
  protected static $itemPrefix = 'slide';

  /**
   * The slick skin manager service.
   *
   * @var \Drupal\slick\SlickSkinManagerInterface
   */
  protected $skinManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setSkinManager($container->get('slick.skin_manager'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderSlick', 'preRenderSlickWrapper'];
  }

  /**
   * Returns slick skin manager service.
   */
  public function skinManager(): SlickSkinManagerInterface {
    return $this->skinManager;
  }

  /**
   * Sets slick skin manager service.
   */
  public function setSkinManager(SlickSkinManagerInterface $skin_manager) {
    $this->skinManager = $skin_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function attachSkin(array &$load, array $attach, $blazies = NULL): void {
    $this->skinManager->attachSkin($load, $attach, $blazies);
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build): array {
    foreach (SlickDefault::themeProperties() as $key => $default) {
      $k = $key == 'items' ? $key : "#$key";
      $build[$k] = $this->toHashtag($build, $key, $default);
    }

    $slick = [
      '#theme'      => 'slick_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSlickWrapper']],
      // Satisfy CTools blocks as per 2017/04/06: 2804165.
      'items'       => [],
    ];

    $this->moduleHandler->alter('slick_build', $slick, $build['#settings']);
    return empty($build['items']) ? [] : $slick;
  }

  /**
   * Returns items as a grid display.
   */
  public function buildGrid(array $items, array &$settings): array {
    $this->verifySafely($settings);

    $blazies = $settings['blazies'];
    $config  = $settings['slicks'];
    $count   = $blazies->get('count', 0);
    $grids   = [];

    // Enforces unslick with less items.
    if (!$config->is('unslick')) {
      $settings['unslick'] = $unslick = $count < $settings['visible_items'];
      $config->set('is.unslick', $unslick);
    }

    // Display all items if unslick is enforced for plain grid to lightbox.
    // Or when the total is less than visible_items.
    if ($config->is('unslick')) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = $count = 2;

      // Requests to refresh grid and re-attach libraries when destroyed.
      $blazies->set('count', $count)
        ->set('is.grid', TRUE)
        ->set('is.grid_refresh', TRUE);

      $grids[0] = $this->buildGridItem($items, 0, $settings);
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = $settings['preserve_keys'] ?? FALSE;
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = $count = count($grid_items);

      $blazies->set('count', $count);
      foreach ($grid_items as $delta => $grid_item) {
        $grids[] = $this->buildGridItem($grid_item, $delta, $settings);
      }
    }

    return $grids;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkins(): array {
    return $this->skinManager->getSkins();
  }

  /**
   * {@inheritdoc}
   */
  public function getSkinsByGroup($group = '', $option = FALSE): array {
    return $this->skinManager->getSkinsByGroup($group, $option);
  }

  /**
   * {@inheritdoc}
   */
  public function loadSafely($name): Slick {
    return Slick::loadSafely($name);
  }

  /**
   * Builds the Slick instance as a structured array ready for ::renderer().
   */
  public function preRenderSlick(array $element): array {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = &$build['#settings'];
    $this->verifySafely($settings);

    $defaults  = Slick::defaultSettings();
    $optionset = &$build['#optionset'];
    $config    = $settings['slicks'];
    $blazies   = $settings['blazies'];

    // Adds helper class if thumbnail on dots hover provided.
    if (!empty($settings['thumbnail_effect']) && (!empty($settings['thumbnail_style']) || !empty($settings['thumbnail']))) {
      $dots_class[] = 'slick-dots--thumbnail-' . $settings['thumbnail_effect'];
    }

    // Adds dots skin modifier class if provided.
    if (!empty($settings['skin_dots'])) {
      $dots_class[] = 'slick-dots--' . str_replace('_', '-', $settings['skin_dots']);
    }

    if (isset($dots_class)) {
      $dots_class[] = $optionset->getSetting('dotsClass') ?: 'slick-dots';
      $js['dotsClass'] = implode(" ", $dots_class);
    }

    // Handle some accessible-slick options.
    if ($settings['library'] == 'accessible-slick'
      && $optionset->getSetting('autoplay')
      && $optionset->getSetting('useAutoplayToggleButton')) {
      foreach (['pauseIcon', 'playIcon'] as $key) {
        if ($value = $optionset->getSetting($key)) {
          if ($classes = trim(strip_tags($value))) {
            if ($classes != $defaults[$key]) {
              $js[$key] = '<span class="' . $classes . '" aria-hidden="true"></span>';
            }
          }
        }
      }
    }

    // Checks for breaking changes: Slick 1.8.1 - 1.9.0 / Accessible Slick.
    // @todo Remove this once the library has permanent solutions.
    if ($config->is('breaking')) {
      if ($optionset->getSetting('rows') == 1) {
        $js['rows'] = 0;
      }
    }

    // Overrides common options to re-use an optionset.
    if ($settings['display'] == 'main') {
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $js[$key] = empty($override) ? FALSE : TRUE;
        }
      }

      // Build the Slick grid if provided.
      $blazies->set('is.grid_nested', TRUE);
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = $this->buildGrid($build['items'], $settings);
      }
      $blazies->set('is.grid_nested', FALSE);
    }

    $build['#attributes'] = $this->prepareAttributes($build);
    $build['#options'] = array_merge($build['#options'], (array) ($js ?? []));

    $this->moduleHandler->alter('slick_optionset', $optionset, $settings);

    foreach (SlickDefault::themeProperties() as $key => $default) {
      $element["#$key"] = $this->toHashtag($build, $key, $default);
    }

    return $element;
  }

  /**
   * One slick_theme() to serve multiple displays: main, overlay, thumbnail.
   */
  public function preRenderSlickWrapper($element): array {
    $build = $element['#build'];
    unset($element['#build']);

    // Prepare settings and assets.
    $settings = $this->prepareSettings($element, $build);

    // Checks if we have thumbnail navigation.
    $thumbs  = $build['thumb']['items'] ?? [];
    $blazies = $settings['blazies'];
    $config  = $settings['slicks'];
    $id      = $blazies->get('css.id', $settings['id'] ?? NULL);

    // Prevents unused thumb going through the main display.
    unset($build['thumb']);

    // Build the main Slick.
    $slick[0] = $this->slick($build);

    // Build the thumbnail Slick.
    // Using $blazies so that elevatezoomplus, etc. can swap Slick/Splide once.
    if ($blazies->is('nav') && $thumbs) {
      $slick[1] = $this->buildNavigation($build, $thumbs, $id);
    }

    // Reverse slicks if thumbnail position is provided to get CSS float work.
    if ($config->get('navpos')) {
      $slick = array_reverse($slick);
    }

    // Collect the slick instances.
    $element['#items'] = $slick;
    $this->setAttachments($element, $settings);

    unset($build);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function toBlazy(array &$data, array &$captions, $delta): void {
    $settings = $this->toHashtag($data);
    $this->verifySafely($settings);

    $blazies = $settings['blazies'];
    $skin    = $settings['skin'] ?? '';
    $prefix  = 'slide';

    $blazies->set('item.id', $prefix)
      ->set('item.prefix', $prefix);

    // Only if it has captions.
    if ($captions) {
      $data['#media_attributes']['class'][] = $prefix . '__media';
      if (strpos($skin, 'full') !== FALSE) {
        $data['#caption_wrapper_attributes']['class'][] = $prefix . '__constrained';
      }
    }

    // Grid already has grid__content wrapper, skip.
    $attrs   = $blazies->get('item.wrapper_attributes', []);
    $wrapper = $blazies->count() > 1;
    $use_ca  = empty($settings['grid']) || $attrs;

    if ($use_ca || $wrapper) {
      if ($use_ca) {
        $data['#wrapper_attributes']['class'][] = $prefix . '__content';
      }

      if ($attrs && $wrapper) {
        $data['#wrapper_attributes'] = $this->merge($data['#wrapper_attributes'], $attrs);
      }
    }

    parent::toBlazy($data, $captions, $delta);
  }

  /**
   * {@inheritdoc}
   */
  public function verifySafely(array &$settings, $key = 'blazies', array $defaults = []) {
    SlickDefault::verify($settings, $this);

    return parent::verifySafely($settings, $key, $defaults);
  }

  /**
   * {@inheritdoc}
   */
  protected function attachments(array &$load, array $attach, $blazies): void {
    parent::attachments($load, $attach, $blazies);
    $this->verifySafely($attach);

    $this->skinManager->attach($load, $attach, $blazies);

    $this->moduleHandler->alter('slick_attach', $load, $attach, $blazies);
  }

  /**
   * Returns items as a grid item display.
   */
  protected function buildGridItem(array $items, $delta, array $settings): array {
    $config  = $settings['slicks'];
    $output  = $this->generateGridItem($items, $settings);
    $result  = $this->toGrid($output, $settings);
    $unslick = $config->is('unslick');

    $result['#attributes']['class'][] = $unslick ? 'slick__grid' : 'slide__content';

    $build = ['slide' => $result, '#settings' => $settings];

    $this->moduleHandler->alter('slick_grid_item', $build, $settings);
    return $build;
  }

  /**
   * Prepare attributes for the known module features, not necessarily users'.
   */
  protected function prepareAttributes(array $build): array {
    $settings   = $this->toHashtag($build);
    $attributes = $this->toHashtag($build, 'attributes');

    if ($settings['display'] == 'main') {
      Blazy::containerAttributes($attributes, $settings);
    }
    return $attributes;
  }

  /**
   * Prepares js-related options.
   */
  protected function prepareOptions(
    Slick &$optionset,
    array &$options,
    array &$settings,
  ): void {
    $blazies    = $settings['blazies'];
    $route_name = $blazies->get('route_name');
    $sandboxed  = $blazies->is('sandboxed');

    // Disable draggable for Layout Builder UI to not conflict with UI sortable.
    $lb = $route_name && strpos($route_name, 'layout_builder.') === 0;
    if ($lb || $sandboxed) {
      $options['draggable'] = FALSE;
    }

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Slick without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    if (!empty($settings['skin'])
      && $skins = $this->skinManager->getSkinsByGroup('main')) {
      if (isset($skins[$settings['skin']]['options'])) {
        $options = array_merge($options, $skins[$settings['skin']]['options']);
      }
    }

    $this->moduleHandler->alter('slick_options', $options, $settings, $optionset);

    // Disabled irrelevant options when lacking of slides.
    $this->unslick($options, $settings);
  }

  /**
   * Prepare settings for the known module features, not necessarily users'.
   */
  protected function prepareSettings(array &$element, array &$build): array {
    $this->hashtag($build);
    $this->hashtag($build, 'options');

    $settings = &$build['#settings'];
    $this->verifySafely($settings);

    $options   = &$build['#options'];
    $optionset = Slick::verifyOptionset($build, $settings['optionset']);
    $blazies   = $settings['blazies'];
    $config    = $settings['slicks'];
    $id        = $blazies->get('css.id', $settings['id'] ?? NULL);
    $id        = $this->getHtmlId('slick', $id);
    $id        = $settings['id'] = 'slick-' . substr(md5($id), 0, 11);
    $thumb_id  = $id . '-nav';
    $count     = $blazies->get('count') ?: $settings['count'] ?? 0;
    $total     = count($build['items']);
    $count     = $count ?: $total;
    $wheel     = $optionset->getSetting('mouseWheel');
    $nav       = $blazies->is('nav', !empty($settings['nav']));
    $navpos    = $settings['thumbnail_position'] ?? NULL;

    // BC for non-required Display style. Blazy 2.5+ requires explicit style.
    if (!empty($settings['grid'])
      && !empty($settings['visible_items'])
      && empty($settings['style'])) {
      $settings['style'] = 'grid';
    }

    // Make it work with ElevateZoomPlus.
    if (!$blazies->is('nav_overridden') && empty($settings['vanilla'])) {
      $nav = !empty($settings['optionset_thumbnail'])
        && isset($build['items'][1]);
    }

    $data = [
      'library'    => $this->config('library', 'slick.settings'),
      'breaking'   => $this->skinManager->isBreaking(),
      'count'      => $count,
      'total'      => $total,
      'nav'        => $nav,
      'navpos'     => ($nav && $navpos) ? $navpos : '',
      'vertical'   => $optionset->getSetting('vertical'),
      'mousewheel' => $wheel,
    ];

    foreach ($data as $key => $value) {
      // @todo remove settings after migration.
      $settings[$key] = $value;
      $config->set(is_bool($value) ? 'is.' . $key : $key, $value);
    }

    // Few dups are generic and needed by Blazy to interop Slick and Splide.
    // The total is the original unmodified count, tricked at grids.
    $blazies->set('css.id', $id)
      ->set('count', $count)
      ->set('total', $total)
      ->set('is.nav', $nav);

    $options['count'] = $count;
    $options['total'] = $total;
    $this->prepareOptions($optionset, $options, $settings);

    if ($blazies->is('nav')) {
      $options['asNavFor'] = "#{$thumb_id}";
      $optionset_tn = $this->loadSafely($settings['optionset_thumbnail']);
      $wheel = $optionset_tn->getSetting('mouseWheel');
      $vertical_tn = $optionset_tn->getSetting('vertical');

      $build['#optionset_tn'] = $optionset_tn;
      $settings['vertical_tn'] = $vertical_tn;
      $config->set('is.vertical_tn', $vertical_tn);
    }
    else {
      // Pass extra attributes such as those from Commerce product variations to
      // theme_slick() since we have no asNavFor wrapper here.
      if ($attributes = $element['#attributes'] ?? []) {
        $attrs = $this->toHashtag($build, 'attributes');
        $build['#attributes'] = $this->merge($attributes, $attrs);
      }
    }

    // Supports Blazy multi-breakpoint or lightbox images if provided.
    // Cases: Blazy within Views gallery, or references without direct image.
    $data = $blazies->get('first.data');
    if ($data && is_array($data)) {
      $this->isBlazy($settings, $data);
    }

    // @todo remove settings after migration.
    $settings['mousewheel'] = $wheel;
    $settings['down_arrow'] = $down_arrow = $optionset->getSetting('downArrow');

    $config->set('is.mousewheel', $wheel)
      ->set('is.down_arrow', $down_arrow);

    $element['#settings'] = $settings;

    return $settings;
  }

  /**
   * Returns slick navigation with the structured array similar to main display.
   */
  protected function buildNavigation(array &$build, array $items, $id): array {
    $settings = $this->toHashtag($build);
    $options  = $build['#options'];

    // Only designed for main display, not thumbnails.
    unset($settings['skin_arrows'], $settings['skin_dots']);

    $settings['optionset'] = $settings['optionset_thumbnail'];
    $settings['skin']      = $settings['skin_thumbnail'];
    $settings['display']   = 'thumbnail';
    $options['asNavFor']   = "#" . $id;
    $data['items']         = $items;
    $data['#optionset']    = $this->toHashtag($build, 'optionset_tn');
    $data['#options']      = $options;
    $data['#settings']     = $settings;

    // Disabled irrelevant options when lacking of slides.
    $this->unslick($options, $settings);

    // The navigation has the same structure as the main one.
    unset($build['#optionset_tn']);
    return $this->slick($data);
  }

  /**
   * Returns a cacheable renderable array of a single slick instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of slick contents: text, image or media.
   *   - #options: An array of key:value pairs of custom JS overrides.
   *   - #optionset: The cached optionset object to avoid multiple invocations.
   *   - #settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a slick instance, or empty array.
   */
  protected function slick(array $build) {
    foreach (SlickDefault::themeProperties() as $key => $default) {
      $k = $key == 'items' ? $key : "#$key";
      $build[$k] = $this->toHashtag($build, $key, $default);
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'slick',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSlick']],
    ];
  }

  /**
   * Generates items as a grid item display.
   */
  private function generateGridItem(array $items, array $settings): \Generator {
    $blazies   = $settings['blazies'];
    $config    = $settings['slicks'];
    $add_class = !$blazies->ui('wrapper_class');

    foreach ($items as $delta => $item) {
      if (!is_array($item)) {
        continue;
      }

      $sets = $this->toHashtag($item);
      $sets += $settings;
      $attrs = $this->toHashtag($item, 'attributes');
      $content_attrs = $this->toHashtag($item, 'content_attributes');
      $sets['current_item'] = 'grid';
      $sets['delta'] = $delta;

      $blazy = $sets['blazies']->reset($sets);
      $blazy->set('delta', $delta);

      // @todo remove after migrations.
      unset(
        $item['settings'],
        $item['attributes'],
        $item['content_attributes'],
        $item['item_attributes']
      );
      if (!$config->is('unslick')) {
        $attrs['class'][] = 'slide__grid';
      }

      $attrs['class'][] = 'grid--' . $delta;

      if ($add_class) {
        foreach (['type', 'media_switch'] as $key) {
          if (!empty($sets[$key])) {
            $value = $sets[$key];
            $attrs['class'][] = 'grid--' . str_replace('_', '-', $value);
            if ($key == 'media_switch' && mb_strpos($value, 'box') !== FALSE) {
              $attrs['class'][] = 'grid--litebox';
            }
          }
        }
      }

      // Listens to signaled attributes via hook_alters.
      $this->gridCheckAttributes($attrs, $content_attrs, $blazies, FALSE);

      $theme = empty($settings['vanilla']) ? 'slide' : 'vanilla';
      $content = [
        '#theme' => 'slick_' . $theme,
        '#item' => $item,
        '#delta' => $delta,
        '#settings' => $sets,
      ];

      $slide = [
        'content' => $content,
        '#attributes' => $attrs,
        '#content_attributes' => $content_attrs,
        '#settings' => $sets,
      ];

      yield $slide;
    }
  }

  /**
   * Disabled irrelevant options when lacking of slides, unslick softly.
   *
   * Unlike `settings.unslick`, this doesn't destroy the markups so that
   * `settings.unslick` can be overriden as needed unless being forced.
   */
  private function unslick(array &$options, array $settings) {
    $config = $settings['slicks'];
    if ($config->get('count') < 2) {
      $options['arrows'] = FALSE;
      $options['dots'] = FALSE;
      $options['draggable'] = FALSE;
      $options['infinite'] = FALSE;
    }
  }

}
