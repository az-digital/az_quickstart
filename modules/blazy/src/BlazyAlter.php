<?php

namespace Drupal\blazy;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Field\FormatterInterface;
use Drupal\blazy\internals\Internals;
use Drupal\editor\Entity\Editor;

/**
 * Provides hook_alter() methods for Blazy.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please use the public method instead.
 */
class BlazyAlter {

  /**
   * The blazy library info.
   *
   * @var array|null
   */
  protected static $libraryInfoBuild;

  /**
   * Implements hook_config_schema_info_alter().
   */
  public static function configSchemaInfoAlter(
    array &$definitions,
    $formatter = 'blazy_base',
    array $settings = [],
  ): void {
    if (isset($definitions[$formatter])) {
      $mappings = &$definitions[$formatter]['mapping'];
      $settings += BlazyDefault::extendedSettings();
      $settings += BlazyDefault::gridSettings();
      $settings += BlazyDefault::svgSettings();
      $settings += BlazyDefault::deprecatedSettings();
      $settings += BlazyDefault::nonBlazySettings();

      foreach ($settings as $key => $value) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($value);
        $type = $type == 'double' ? 'float' : $type;
        $mappings[$key]['type'] = is_array($value) ? 'sequence' : $type;

        if (!is_array($value)) {
          $mappings[$key]['label'] = Unicode::ucfirst(str_replace('_', ' ', $key));
        }
      }
    }
  }

  /**
   * Implements hook_library_info_alter().
   */
  public static function libraryInfoAlter(&$libraries, $extension): void {
    // @todo remove if core changed, right below core/drupal for being generic,
    // and dependency-free and a dependency for many other generic ones.
    // @todo watch out for core @todo to remove drupal namespace for debounce.
    $debounce = 'drupal.debounce';
    if ($extension === 'core' && isset($libraries[$debounce])) {
      $libraries[$debounce]['js']['misc/debounce.js'] = ['weight' => -16];
    }

    if ($extension === 'media' && isset($libraries['oembed.frame'])) {
      $libraries['oembed.frame']['dependencies'][] = 'blazy/oembed';
    }

    // Blazy colorbox needs these higher.
    foreach (BlazyDefault::thirdPartyLibraries() as $module => $libs) {
      if ($extension === $module) {
        foreach ($libs as $id => $lib) {
          if (isset($libraries[$id]) && $js = $lib['js']) {
            $libraries[$id]['js'][$js]['weight'] = $lib['weight'];

            // See https://stackoverflow.com/questions/10808109
            if ($attributes = $lib['attributes'] ?? []) {
              $libraries[$id]['js'][$js]['attributes'] = $attributes;
            }
          }
        }
      }
    }

    if ($extension === 'blazy') {
      if ($manager = Internals::service('blazy.manager')) {
        $names = ['DOMPurify', 'dompurify'];
        if ($path = $manager->getLibrariesPath($names)) {
          $js = [
            '/' . $path . '/dist/purify.min.js' => [
              'minified' => TRUE,
              'weight' => -16,
            ],
          ];
          $libraries['dompurify']['js'] = $js;
          $libraries['dblazy']['dependencies'][] = 'blazy/dompurify';
        }
      }
    }
  }

  /**
   * Implements hook_library_info_build().
   */
  public static function libraryInfoBuild() {
    if (!isset(static::$libraryInfoBuild)) {
      $libraries = [];
      // Optional polyfills for IEs, and oldies.
      $polyfills = array_merge(BlazyDefault::polyfills(), BlazyDefault::ondemandPolyfills());
      foreach ($polyfills as $id) {
        // Matches common core polyfills' weight.
        $weight = $id == 'polyfill' ? -21 : -20;
        $weight = $id == 'webp' ? -5.5 : $weight;
        $common = ['minified' => TRUE, 'weight' => $weight];
        $libraries[$id] = [
          'js' => [
            'js/polyfill/blazy.' . $id . '.min.js' => $common,
          ],
        ];

        if ($id == 'webp') {
          $libraries[$id]['dependencies'][] = 'blazy/dblazy';
        }
      }

      // Plugins extending dBlazy.
      foreach (BlazyDefault::plugins() as $id) {
        $base = ['eventify', 'viewport', 'dataset'];
        $base = in_array($id, $base);
        $deps = $base ? ['blazy/dblazy', 'blazy/base'] : ['blazy/xlazy'];
        if ($id == 'xlazy') {
          $deps = ['blazy/viewport', 'blazy/dataset'];
        }

        // @todo problematic weight, basically compat must be present.
        if (in_array($id, ['animate', 'background'])) {
          $deps[] = 'blazy/compat';
        }
        $weight = $base ? -5.6 : -5.5;

        $common = ['minified' => TRUE, 'weight' => $weight];
        $libraries[$id] = [
          'js' => [
            'js/plugin/blazy.' . $id . '.min.js' => $common,
          ],
          'dependencies' => $deps,
        ];
      }

      // Components, normally non-generic, unlike plugins.
      foreach (BlazyDefault::dyComponents() as $id => $component) {
        $libraries[$id] = $component;
      }

      static::$libraryInfoBuild = $libraries;
    }
    return static::$libraryInfoBuild;
  }

  /**
   * Checks if Entity/Media Embed is enabled.
   */
  public static function isCkeditorApplicable(Editor $editor): bool {
    foreach (['entity_embed', 'media_embed'] as $filter) {
      if (!$editor->isNew()
        && $editor->getFilterFormat()->filters()->has($filter)
        && $editor->getFilterFormat()
          ->filters($filter)
          ->getConfiguration()['status']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Implements hook_ckeditor_css_alter().
   */
  public static function ckeditorCssAlter(array &$css, Editor $editor): void {
    if (self::isCkeditorApplicable($editor)) {
      $path = Internals::getPath('module', 'blazy', TRUE);
      $css[] = $path . '/css/components/blazy.media.css';
      $css[] = $path . '/css/components/blazy.preview.css';
      $css[] = $path . '/css/components/blazy.ratio.css';
    }
  }

  /**
   * Provides the third party formatters where full blown Blazy is not worthy.
   */
  public static function thirdPartyFormatters(): array {
    $formatters = ['file_audio', 'file_video'];
    if ($manager = Internals::service('blazy.manager')) {
      $formatters = $manager->thirdPartyFormatters();
    }
    return array_unique($formatters);
  }

  /**
   * Implements hook_field_formatter_third_party_settings_form().
   */
  public static function fieldFormatterThirdPartySettingsForm(FormatterInterface $plugin): array {
    if (in_array($plugin->getPluginId(), self::thirdPartyFormatters())) {
      return [
        'blazy' => [
          '#type' => 'checkbox',
          '#title' => 'Blazy',
          '#default_value' => $plugin->getThirdPartySetting('blazy', 'blazy', FALSE),
        ],
      ];
    }
    return [];
  }

  /**
   * Implements hook_field_formatter_settings_summary_alter().
   */
  public static function fieldFormatterSettingsSummaryAlter(array &$summary, $context): void {
    if ($formatter = $context['formatter'] ?? NULL) {
      $on = $formatter->getThirdPartySetting('blazy', 'blazy', FALSE);
      if ($on && in_array($formatter->getPluginId(), self::thirdPartyFormatters())) {
        $summary[] = 'Blazy';
      }

      // Provide removal message, applicable to all Blazy ecosystem.
      $plugin_id = $formatter->getPluginId();
      if (strpos($plugin_id, '_file') !== FALSE) {
        $config = $formatter->getSettings();
        // All blazy file ecosystem has this unique option.
        if (isset($config['svg_hide_caption'])) {
          $definition = $context['field_definition'];
          $settings   = $definition->getSettings();
          $extensions = $settings['file_extensions'] ?? '';
          $plugin     = $formatter->getPluginDefinition();

          if (!Blazy::has($extensions, 'svg') && $definition->getType() == 'image') {
            $summary[] = t('<h5>No SVG file extensions, use @provider Image instead.</h5>', [
              '@provider' => Unicode::ucfirst($plugin['provider']),
            ]);
          }
        }
      }
    }
  }

  /**
   * Implements hook_blazy_settings_alter().
   *
   * Provides minimal flags for Blazy field formatters embedded inside a view.
   * With this limited info, sub-modules like Splidebox can correctly inject
   * its options via [data-splidebox] to the correct container, etc., and avoid
   * duplicating injections at both embedded Blazy formatter and Blazy Grid view
   * style. And the same principle applies to all sub-modules.
   *
   * Warning! Do not alter configurable settings like use_theme_field here, it
   * caused 2.16 chaotic markups with Views embedded blazy formatters.
   */
  public static function blazySettingsAlter(array &$build, $object): void {
    $settings = &$build['#settings'];
    $blazies  = $settings['blazies'];

    // Adds bio.ajax to fix product variation AJAX within BigPipe.
    // Views AJAX will automatically work, however to support other non-views
    // AJAX, add more conditions to your custom hook_blazy_settings_alter.
    if ($type = $blazies->get('field.entity_type')) {
      if ($type == 'commerce_product_variation') {
        $blazies->set('use.ajax', TRUE);
      }
    }

    // Sniffs for Views to allow block__no_wrapper, views_no_wrapper, etc.
    $function = 'views_get_current_view';
    // @todo phpstan bug, misleading with nullable function return.
    /* @phpstan-ignore-next-line */
    if (is_callable($function) && $view = $function()) {
      $name      = $view->storage->id();
      $view_mode = $view->current_display;
      $style     = $view->style_plugin;
      $display   = $style ? $style->displayHandler->getPluginId() : '';
      $plugin_id = $style ? $style->getPluginId() : '';

      // Only eat what we can chew:
      $data = Internals::getViewFieldData($view);
      $current = [
        'count'       => count($view->result),
        'display'     => $display,
        'embedded'    => TRUE,
        'instance_id' => str_replace('_', '-', "{$name}-{$display}-{$view_mode}"),
        'data'        => $data,
        'multifield'  => count($data['fields']) > 1,
        'name'        => $name,
        'plugin_id'   => $plugin_id,
        'view_mode'   => $view_mode,
      ];

      // Collects view info for the embedded Blazy, and this is not a view.
      $blazies->set('view', $current, TRUE)
        ->set('is.view', FALSE);
    }
  }

}
