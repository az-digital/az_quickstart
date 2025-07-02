<?php

namespace Drupal\blazy;

use Drupal\blazy\internals\Internals;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * Be informed! Even with these massive settings, it is just scratching the
 * Media integration surfaces, many were left out for custom works.
 */
class BlazyDefault {

  /**
   * Defines default constants for the supported fixed aspect ratios.
   *
   * These are related to convention in css/blazy.ratio.css.
   */
  const RATIO = ['1:1', '3:2', '4:3', '8:5', '16:9'];

  /**
   * Defines constant for the supported text tags.
   */
  const TAGS = [
    'a',
    'em',
    'strong',
    'h2',
    'h3',
    'p',
    'small',
    'span',
    'ul',
    'ol',
    'li',
  ];

  /**
   * Defines constant for the supported media tags.
   *
   * @todo recheck if OEmbed has <iframe>, <embed>, <object>, <track>, etc.
   */
  const MEDIA_TAGS = [
    'audio',
    'div',
    'figcaption',
    'figure',
    'img',
    'picture',
    'source',
    'span',
    'video',
  ];

  /**
   * Provides the object ID to initialize BlazySettings. slicks, masons, etc.
   *
   * @var string|null
   */
  protected static $id = NULL;

  /**
   * Returns alterable plugin settings to pass the tests.
   *
   * @param array $settings
   *   The settings being modified.
   */
  public static function alterableSettings(array &$settings) {
    if ($manager = Internals::service('blazy.manager')) {
      $context = ['class' => get_called_class()];
      $manager->moduleHandler()->alter('blazy_base_settings', $settings, $context);
    }
  }

  /**
   * Returns basic plugin settings.
   */
  public static function baseSettings() {
    $settings = ['cache' => 0, 'admin_uri' => '', 'use_lb' => FALSE];

    self::alterableSettings($settings);
    return $settings;
  }

  /**
   * Returns cherry-picked settings for field formatters and Views fields.
   */
  public static function cherrySettings() {
    return [
      'background'      => FALSE,
      'box_style'       => '',
      'image_style'     => '',
      'media_switch'    => '',
      'ratio'           => '',
      'thumbnail_style' => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function baseImageSettings() {
    return [
      'background'             => FALSE,
      'box_caption'            => '',
      'box_caption_custom'     => '',
      'box_media_style'        => '',
      'caption'                => [],
      'loading'                => 'lazy',
      'preload'                => FALSE,
      'responsive_image_style' => '',
      'use_theme_field'        => FALSE,
      'use_lb'                 => FALSE,
    ] + self::cherrySettings();
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function imageSettings() {
    return [
      'by_delta'  => -1,
      'layout'    => '',
      'link'      => '',
      'view_mode' => '',
    ] + self::baseSettings()
      + self::baseImageSettings();
  }

  /**
   * Returns svg-related field formatter settings.
   */
  public static function svgSettings() {
    return [
      'svg_inline' => FALSE,
      'svg_fill' => FALSE,
      'svg_sanitize' => TRUE,
      'svg_sanitize_remote' => FALSE,
      'svg_hide_caption' => FALSE,
      'svg_attributes' => '',
    ];
  }

  /**
   * Returns media-related field formatter settings.
   */
  public static function mediaSettings() {
    return [
      'link' => '',
    ] + self::svgSettings();
  }

  /**
   * Returns non-prefixed svg-related options.
   */
  public static function toSvgOptions(array $settings) {
    $options = [];
    foreach (array_keys(self::svgSettings()) as $key) {
      if (isset($settings[$key])) {
        $k = str_replace('svg_', '', $key);
        $options[$k] = $settings[$key];
      }
    }
    return $options;
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsSettings() {
    return [
      'class'   => '',
      'image'   => '',
      'link'    => '',
      'overlay' => '',
      'title'   => '',
      'vanilla' => FALSE,
    ];
  }

  /**
   * Returns fieldable entity formatter and Views settings.
   */
  public static function extendedSettings() {
    return self::viewsSettings() + self::imageSettings();
  }

  /**
   * Returns optional grid field formatter and Views settings.
   */
  public static function gridSettings() {
    return [
      'grid'        => '',
      'grid_medium' => '',
      'grid_small'  => '',
      'style'       => '',
    ];
  }

  /**
   * Returns sensible default options common for OEmbed within views.
   */
  public static function mediaDefaults() {
    return [
      'media_switch' => 'media',
      'ratio' => 'fluid',
      'view_mode' => 'default',
    ];
  }

  /**
   * Returns sensible default options common for Views lacking of UI.
   */
  public static function lazySettings() {
    return [
      'blazy' => TRUE,
      'lazy'  => 'blazy',
    ] + self::mediaDefaults();
  }

  /**
   * Returns sensible default options common for entities lacking of UI.
   */
  public static function entitySettings() {
    return [
      'rendered'     => FALSE,
      '_detached'    => TRUE,
    ] + self::lazySettings();
  }

  /**
   * Returns default options common for rich Media entities: Facebook, etc.
   *
   * This basically disables few Blazy features for rendered-entity-like.
   */
  public static function richSettings() {
    return [
      'background'   => FALSE,
      'media_switch' => '',
    ];
  }

  /**
   * Returns minimum grid and style settings.
   */
  public static function gridEntitySettings() {
    return self::gridSettings() + ['view_mode' => ''];
  }

  /**
   * Returns common image properties.
   */
  public static function imageProperties() {
    return ['uri', 'width', 'height', 'target_id', 'alt', 'title', 'entity'];
  }

  /**
   * Returns common image styles.
   */
  public static function imageStyles() {
    return ['box', 'box_media', 'image', 'thumbnail'];
  }

  /**
   * Returns common media bundles with hi-res image posters.
   *
   * @todo adjust if anything better than unreliable bundles.
   */
  public static function imagePosters() {
    return [
      'audio',
      'remote_video',
      'video',
      'd500px',
      'facebook',
      'imgur',
      'instagram',
      'pinterest',
      'slideshare',
      'soundcloud',
      'spotify',
      'twitter',
    ];
  }

  /**
   * Returns shared global form settings which should be consumed at formatters.
   */
  public static function uiSettings() {
    return [
      'blur_client'         => FALSE,
      'blur_storage'        => FALSE,
      'blur_minwidth'       => 0,
      'fx'                  => '',
      'nojs'                => [],
      'one_pixel'           => TRUE,
      'visible_class'       => FALSE,
      'noscript'            => FALSE,
      'placeholder'         => '',
      'unstyled_extensions' => '',
    ];
  }

  /**
   * Returns sensible default container settings to shutup notices when lacking.
   */
  public static function htmlSettings() {
    return [
      'blazies' => self::toSettings(self::blazies()),

      // Configurable settings are dumped as they are as always.
      // Very few are adjusted into blazies for easy calls/overrides/alters.
    ] + self::imageSettings()
      + self::gridSettings()
      + self::objectify();
  }

  /**
   * Returns blazy theme properties, its image and container attributes.
   *
   * The reserved attributes is defined before entering Blazy as bonus variable.
   * Consider other bonuses: title and content attributes at a later stage.
   * Layering is crucial for mixed media, cannot be simply dumped as
   * indexed children, must have clear properties indentifying their functions.
   *
   * @done prefix non-renderable with # at/by 3.x to minimize render errors.
   * The first error was identified with BVEF due to being out of sync when
   * given an extra property `entity` as seen at BlazyEntity::build().
   * No issues so far with all these, yet conversions will eliminate any.
   * Initial effort was via Blazy::toHashtag() checkpoint till full migration.
   */
  public static function themeProperties() {
    return [
      'captions' => [],
      'iframe' => [],
      'image' => [],
      'noscript' => [],
    ] + self::themeContents()
      + self::hashedProperties();
  }

  /**
   * Returns optional extra content variables other than the basic above.
   */
  public static function themeContents() {
    return [
      'content' => [],
      'icon' => [],
      'overlay' => [],
      'postscript' => [],
      'preface' => [],
    ];
  }

  /**
   * Returns non-renderable blazy theme properties to avoid render errors.
   *
   * No issues when all these were passed into theme_blazy() since 1.x, except
   * when they enter theme_item_list() or theme_field() as a leak or by mistake.
   */
  public static function hashedProperties() {
    return [
      'attributes' => [],
      'delta' => 0,
      'item' => NULL,
      'item_attributes' => [],
      'settings' => [],
      'url' => NULL,
    ];
  }

  /**
   * Returns additional/ optional blazy theme attributes.
   *
   * The attributes mentioned here are only instantiated at theme_blazy() and
   * might be an empty array, not instanceof \Drupal\Core\Template\Attribute.
   * All will be suffixed with "_attributes", e.g.: caption_attributes, etc.
   */
  public static function themeAttributes() {
    return [
      'caption',
      'caption_wrapper',
      'caption_content',
      'media',
      'url',
      'wrapper',
    ];
  }

  /**
   * Returns available components.
   */
  public static function components(): array {
    $components = array_merge(self::grids(), [
      'animate',
      'background',
      'blur',
      'compat',
      'filter',
      'media',
      'mfp',
      'ratio',
    ]);
    return array_merge($components, array_keys(self::dyComponents()));
  }

  /**
   * Returns available dynamic components, not registered in libraries.yml.
   */
  public static function dyComponents(): array {
    $deps   = ['blazy/compat'];
    $common = ['minified' => TRUE, 'weight' => -1.8];
    $libs   = [];

    foreach (['instagram', 'pinterest'] as $key) {
      $libs[$key] = [
        'js' => ['js/components/provider/blazy.' . $key . '.min.js' => $common],
        'dependencies' => $deps,
      ];
    }

    return $libs;
  }

  /**
   * Returns available grid components.
   */
  public static function grids(): array {
    return [
      'column',
      'flex',
      'flexbox',
      'grid',
      'nativegrid',
      'nativegrid.masonry',
    ];
  }

  /**
   * Returns available plugins.
   */
  public static function plugins(): array {
    return [
      'eventify',
      'viewport',
      'xlazy',
      'animate',
      'dataset',
      'background',
      'observer',
      'multimedia',
    ];
  }

  /**
   * Returns available nojs components related to core Blazy functionality.
   */
  public static function polyfills(): array {
    return [
      'polyfill',
      'classlist',
      'promise',
      'raf',
      'webp',
    ];
  }

  /**
   * Returns available nojs components related to core Blazy functionality.
   */
  public static function nojs(): array {
    return array_merge(['lazy'], self::polyfills());
  }

  /**
   * Returns optional polyfills, not loaded till enabled and a feature meets.
   */
  public static function ondemandPolyfills(): array {
    return [
      'fullscreen',
    ];
  }

  /**
   * Returns deprecated, or previously wrong room settings.
   *
   * Only needed by 1.x/old users who never re-saved the forms at 2.x. This is
   * easily solved by just re-saving them. And these will be just gone for good.
   *
   * @todo deprecated/ removed at 3.x.
   */
  public static function deprecatedSettings() {
    return [
      'current_view_mode' => '',
      'fx' => '',
      'grid_header' => '',
      'icon' => '',
      'id' => '',
      'lazy'  => 'blazy',
      'sizes' => '',
      '_item' => '',
      '_uri' => '',
      'breakpoints' => '',
    ];
  }

  /**
   * Returns wrong room settings, since initially copied from Slick.
   */
  public static function nonBlazySettings() {
    return [
      'skin' => '',
      'optionset' => '',
    ];
  }

  /**
   * Returns a BlazySettings instance.
   */
  public static function toSettings(array $data = []): BlazySettings {
    return Internals::settings($data);
  }

  /**
   * Returns third party libraries that colorbox, etc. need these higher.
   */
  public static function thirdPartyLibraries(): array {
    return [
      'media_entity_instagram' => [
        'instagram.embeds' => [
          'js' => '//platform.instagram.com/en_US/embeds.js',
          'weight' => -16,
          'attributes' => ['defer' => TRUE],
        ],
        'integration' => [
          'js' => 'js/instagram.js',
          'weight' => -6,
        ],
      ],
      'media_entity_pinterest' => [
        'pinterest.widgets' => [
          'js' => 'https://assets.pinterest.com/js/pinit.js',
          'weight' => -16,
          'attributes' => ['defer' => TRUE],
        ],
        'integration' => [
          'js' => 'js/pinterest.js',
          'weight' => -6,
        ],
      ],
    ];
  }

  /**
   * Returns options for the object conversions.
   */
  protected static function options(): array {
    return [];
  }

  /**
   * Returns values to be converted to a BlazySettings instance.
   */
  protected static function values(): array {
    return [];
  }

  /**
   * Grouping for sanity till all settings converted into BlazySettings.
   *
   * It was a pre-release RC7 @todo, partially implemented since 2.7.
   * The hustle is sub-modules are not aware, yet. Yet better started before 3.
   * While some configurable settings are intact, blazies are more for grouping
   * dynamic, non-configurable settings. But it can also store blazy-specific.
   * Very few are adjusted into blazies for easy calls/overrides/alters.
   * Please bear with the silly plural `blazies` object, no better ideas.
   */
  private static function blazies() {
    $ui = self::uiSettings();

    // For convenience when by-passing the provided API.
    if ($manager = Internals::service('blazy.manager')) {
      $ui = $manager->config();
    }
    return [
      'initial' => 0,
      'is' => [],
      'lazy' => ['id' => 'blazy', 'attribute' => 'src', 'class' => 'b-lazy'],
      'libs' => [],
      'ui' => $ui,
      'use' => [],
    ];
  }

  /**
   * Returns BlazySettings instance keyed by static::$id.
   */
  private static function objectify(): array {
    $items   = [];
    $options = self::options();
    $managed = $options['managed'] ?? FALSE;

    if (!$managed && $values = self::values()) {
      foreach ($values as $key => $value) {
        if (is_bool($value)) {
          if (strpos($key, 'use_') !== FALSE) {
            $key = str_replace('use_', '', $key);
            $items['use'][$key] = $value;
          }
          else {
            if ($options['ltrim'] ?? FALSE) {
              $key = ltrim($key, '_');
            }

            $items['is'][$key] = $value;
          }
        }
        else {
          if (strpos($key, 'item_') !== FALSE) {
            $key = str_replace('item_', '', $key);
            $items['item'][$key] = $value;
          }
          $items[$key] = $value;
        }
      }
    }

    if ($id = static::$id) {
      return [$id => self::toSettings($items)];
    }
    return [];
  }

}
