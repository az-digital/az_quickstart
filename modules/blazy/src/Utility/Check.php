<?php

namespace Drupal\blazy\Utility;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Field\BlazyField;
use Drupal\blazy\Theme\BlazyViews;
use Drupal\blazy\internals\Internals;

/**
 * Provides feature check methods at container level, or globally.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module. Please use the public method instead.
 *
 * @todo refine, and split them conditionally based on fields like libraries.
 * @todo remove most $settings once migrated and after sub-modules and tests.
 */
class Check {

  /**
   * Checks for container stuffs, mostly re-definition in case set earlier.
   *
   * @todo remove some settings after sub-modules.
   */
  public static function container(array &$settings): void {
    $blazies      = $settings['blazies'];
    $item_id      = $blazies->get('item.id', 'blazy');
    $item_caption = $blazies->get('item.caption', 'captions');
    $item_prefix  = $blazies->get('item.prefix', 'blazy');
    $namespace    = $blazies->get('namespace', $settings['namespace'] ?? 'blazy');

    self::uiContainer($settings);

    // Some should be refined per item against potential mixed media items.
    // @todo move some into Blazy::prepare() as might be called per item.
    $stage = $settings['image'] ?? NULL;
    $stage = $blazies->get('field.formatter.image', $stage);
    $blazies->set('is.hires', !empty($stage))
      ->set('item.id', $item_id)
      ->set('item.caption', $item_caption)
      ->set('item.prefix', $item_prefix)
      ->set('namespace', $namespace)
      ->set('was.container', TRUE);
  }

  /**
   * Checks for container defined by UI, where Blazy is not the formatter.
   *
   * Mostly for third party settings, using the global UI settings.
   */
  public static function uiContainer(array &$settings): void {
    $blazies      = $settings['blazies'];
    $ui           = $blazies->get('ui');
    $bundles      = $blazies->get('field.target_bundles', []);
    $medias       = $blazies->get('media.defaults', BlazyDefault::mediaDefaults());
    $ratios       = $blazies->get('css.ratio', BlazyDefault::RATIO);
    $is_audio     = $bundles && in_array('audio', $bundles);
    $is_video     = $bundles && in_array('video', $bundles);
    $_loading     = $settings['loading'] ?? '';
    $loading      = $settings['loading'] = $_loading ?: 'lazy';
    $is_preview   = Path::isPreview();
    $is_amp       = Path::isAmp();
    $is_sandboxed = Path::isSandboxed();
    $is_bg        = !empty($settings['background']);
    $is_unload    = !empty($ui['nojs']['lazy']);
    $is_slider    = $loading == 'slider';
    $is_unloading = $loading == 'unlazy';
    $is_defer     = $loading == 'defer';
    $is_fluid     = ($settings['ratio'] ?? '') == 'fluid';
    $is_static    = $is_preview || $is_amp || $is_sandboxed;
    $is_undata    = $is_static || $is_unloading;
    $is_nojs      = $is_unload || $is_undata;
    $is_resimage  = is_callable('responsive_image_get_mime_type');
    $is_resimage  = $blazies->is('resimage', $is_resimage);
    $svg_exist    = Blazy::svgSanitizerExists();

    // When `defer` is chosen, overrides global `No JavaScript: lazy`, ensures
    // to not affect AMP, CKEditor, or other preview pages where nojs is a must.
    if ($is_nojs && $is_defer) {
      $is_nojs = $is_undata;
    }

    // Compat is anything that Native lazy doesn't support.
    $is_compat = $is_bg
      || $is_fluid
      || $is_audio
      || $is_video
      || $is_defer
      || $blazies->get('fx')
      || $blazies->get('libs.compat');

    // @todo remove is.bg for use.bg at 3.x:
    $blazies->set('is.bg', $is_bg);

    // Some should be refined per item against potential mixed media items.
    // @todo move some into Blazy::prepare() as might be called per item.
    // @todo remove some overlaps is for use.
    $blazies->set('css.ratio', $ratios, TRUE)
      ->set('image.loading', $loading)
      ->set('is.amp', $is_amp)
      ->set('is.blazy', TRUE)
      ->set('is.fluid', $is_fluid)
      ->set('is.nojs', $is_nojs)
      ->set('is.preview', $is_preview)
      ->set('is.resimage', $is_resimage)
      ->set('is.sandboxed', $is_sandboxed)
      ->set('is.slider', $is_slider)
      ->set('is.static', $is_static)
      ->set('is.svg_sanitizer', $svg_exist)
      ->set('is.undata', $is_undata)
      ->set('is.unload', $is_unload)
      ->set('is.unloading', $is_unloading)
      ->set('is.unlazy', $is_nojs)
      ->set('lazy.html', !empty($ui['lazy_html']))
      ->set('libs.background', $is_bg || $is_audio)
      ->set('libs.compat', $is_compat)
      ->set('libs.ratio', !empty($settings['ratio']))
      ->set('media.defaults', $medias)
      ->set('use.bg', $is_bg)
      ->set('use.dataset', $is_bg || $is_video)
      ->set('use.encodedbox', !empty($ui['use_encodedbox']))
      ->set('use.image', TRUE)
      ->set('use.loader', !$is_nojs)
      ->set('use.script', FALSE)
      ->set('use.svg_dimensions', TRUE);
  }

  /**
   * Checks for Blazy formatter such as from within a Views style plugin.
   *
   * @see \Drupal\blazy\Blazy::preserve()
   * @see \Drupal\blazy\BlazyManager::isBlazy()
   */
  public static function blazyOrNot(array &$settings, array $data = []): void {
    // Retrieves Blazy formatter related settings from within Views style.
    $blazies = Internals::verify($settings);
    $data    = $data ?: $blazies->get('first.data');

    if (empty($data) || !is_array($data)) {
      return;
    }

    // 1. Blazy formatter within Views styles by supported modules.
    // $item_id might be slide, box, etc.
    $subsets = Internals::toHashtag($data);
    $item_id = $blazies->get('item.id');
    $content = $data[$item_id] ?? $data;

    // 2. Blazy Views fields by supported modules.
    // Prevents edge case with unexpected flattened Views results which is
    // normally triggered by checking "Use field template" option.
    // Flattenings were seen at D7, but no longer seen at D9, however...
    if (is_array($content) && ($view = ($content['#view'] ?? NULL))) {
      if ($blazy_field = BlazyViews::viewsField($view)) {
        $subsets = $blazy_field->mergedViewsSettings();
        $settings = array_merge(array_filter($subsets), array_filter($settings));
      }
    }

    // 3. Core image formatter.
    if (!$subsets && $image_style = $data['#image_style'] ?? NULL) {
      $subsets['image_style'] = $settings['image_style'] = $image_style;
    }

    // 4. Makes this container aware of Blazy formatter it might contain.
    if ($subsets) {
      Internals::preserve($settings, $subsets);

      // Rechecks container, etc. since we have $subsets.
      if ($manager = Internals::service('blazy.manager')) {
        $blazies->set('was.initialized', FALSE);
        $manager->preSettings($settings);
      }
    }

    // 4. No longer needed once extracted above, remove.
    $blazies->unset('first.data')
      ->set('was.blazy', TRUE);
  }

  /**
   * Checks for field formatter settings.
   *
   * @todo remove fallback settings after migration and sub-modules.
   */
  public static function fields(array &$settings, $items): void {
    $entity = $items->getEntity();

    Blazy::entitySettings($settings, $entity);

    $blazies = $settings['blazies'];
    if ($blazies->was('field')) {
      return;
    }

    // @todo remove after sub-modules.
    $field = $items->getFieldDefinition();
    if (!$blazies->get('field')) {
      BlazyField::settings($settings, $field);
    }

    // @fixme might be 0 even has one if embedded inside LB blocks.
    $total       = $items->count();
    $count       = $blazies->get('count', $total);
    $field_name  = $blazies->get('field.name');
    $field_clean = str_replace('field_', '', $field_name);
    $entity_type = $blazies->get('entity.type_id');
    $entity_id   = $blazies->get('entity.id');
    $bundle      = $blazies->get('entity.bundle');
    $view_mode   = $blazies->get('field.view_mode', 'default');
    $namespace   = $blazies->get('namespace', 'blazy');
    $id          = $blazies->get('css.id', '');
    $gallery_id  = "{$namespace}-{$entity_type}-{$bundle}-{$field_clean}-{$view_mode}";
    $id          = Internals::getHtmlId("{$gallery_id}-{$entity_id}", $id);
    $switch      = $settings['media_switch'] ?? NULL;
    $switch      = $switch ?: $blazies->get('switch');

    // When alignment is mismatched, split them to satisfy linter.
    // Respects linked_field.module expectation.
    $linked    = $blazies->get('field.third_party.linked_field.linked');
    $use_field = !$blazies->is('lightbox') && $linked;
    $use_field = $use_field || !empty($settings['use_theme_field']);

    if (is_string($settings['by_delta'])) {
      $settings['by_delta'] = (int) $settings['by_delta'];
    }

    // @todo remove, used by sliders at twigs.
    $settings['count'] = $count;
    $settings['id'] = $id;
    $settings['use_theme_field'] = $use_field;

    if ($switch && $blazies->is('lightbox')) {
      $gallery_id = str_replace('_', '-', $gallery_id . '-' . $switch);
      $blazies->set('lightbox.gallery_id', $gallery_id);
    }

    // The total is the original unmodified count, tricked at slider grids.
    $blazies->set('cache.metadata.keys', [$id, $count], TRUE)
      ->set('cache.metadata.tags', [$entity_type . ':' . $entity_id], TRUE)
      ->set('count', $count)
      ->set('total', $count)
      ->set('css.id', $id)
      ->set('use.theme_field', $use_field)
      ->set('was.field', TRUE);
  }

  /**
   * Checks for grids, also supports Slick which requires no `style`.
   */
  public static function grids(array &$settings): void {
    $blazies  = $settings['blazies'];
    $has_grid = !empty($settings['grid']);
    $sub_grid = $has_grid && !empty($settings['visible_items']);
    $style    = $settings['style'] ?? NULL;
    $style    = $style ?: ($sub_grid ? 'grid' : NULL);
    $is_grid  = $sub_grid ?: ($style && $has_grid);
    $is_grid  = $is_grid ?: $settings['_grid'] ?? $blazies->is('grid', $is_grid);

    $blazies->set('is.grid', $is_grid);

    // Bail out early if not so configured.
    if (!$is_grid) {
      return;
    }

    // Babysitter for Slick which requires no Display style.
    if (!$style) {
      $settings['style'] = 'grid';
    }

    if ($style) {
      foreach (BlazyDefault::grids() as $grid) {
        if ($style == $grid) {
          $key = str_replace('.', '__', $style);
          $blazies->set('libs.' . $key, $grid);
        }
      }

      // Formatters, Views style, not Filters.
      Internals::toNativeGrid($settings);
    }

    $blazies->set('was.grid', TRUE);
  }

  /**
   * Checks for lightboxes.
   */
  public static function lightboxes(array &$settings): void {
    $blazies = $settings['blazies'];
    $switch  = $blazies->get('switch', $settings['media_switch'] ?? NULL);
    $manager = Internals::service('blazy.manager');

    // Bail out early if not so configured.
    if (!$switch || !$manager) {
      return;
    }

    $lightboxes = $blazies->get('lightbox.plugins', $manager->getLightboxes());
    $lightbox   = in_array($switch, $lightboxes) ? $switch : FALSE;
    $optionset  = empty($settings[$switch]) ? $switch : $settings[$switch];

    // Lightbox is unique, safe to reserve top level key:
    if ($lightbox) {
      // Required by sub-modules for easy attachments.
      $settings[$switch] = $optionset;

      // Allows lightboxes to provide its own optionsets, e.g.: ElevateZoomPlus.
      // With an optionset: `elevetazoomplus:responsive`.
      // Without an optionset: `colorbox:colorbox`, etc.
      $blazies->set($switch, $optionset)
        ->set('lightbox.name', $lightbox)
        ->set('lightbox.optionset', $optionset);
    }

    // Richbox is local video inside lightboxes by supported lightboxes.
    $colorbox   = $blazies->get('colorbox');
    $flybox     = $blazies->get('flybox');
    $mfp        = $blazies->get('mfp');
    $encodedbox = $colorbox || $flybox || $mfp;
    $encodedbox = $blazies->is('encodedbox') || $encodedbox;
    $_richbox   = $blazies->is('richbox') ?: ($settings['_richbox'] ?? FALSE);
    $richbox    = $encodedbox || $_richbox;

    // (Non-)lightboxes: media player, link to content, image rendered, etc.
    $blazies->set('switch', $switch)
      ->set('libs.media', $switch == 'media')
      ->set('is.lightbox', !empty($lightbox))
      ->set('is.encodedbox', !empty($encodedbox))
      ->set('is.richbox', !empty($richbox))
      ->set('was.lightbox', TRUE);
  }

  /**
   * Checks for settings alter.
   */
  public static function settingsAlter(array &$settings, $entity = NULL): void {
    $blazies = $settings['blazies'];
    $manager = Internals::service('blazy.manager');

    // Bail out early if not so configured.
    if (!$blazies->is('lightbox') || !$manager) {
      return;
    }

    // Gallery is determined by a view, or overriden by colorbox settings.
    // Might be set by formatters or filters, but not View styles/ fields.
    $gallery_id = $blazies->get('view.instance_id');
    $gallery_id = $blazies->get('lightbox.gallery_id') ?: $gallery_id;
    $is_gallery = !empty($gallery_id);

    // Respects colorbox settings unless for an explicit field/ view gallery.
    if (!$is_gallery
      && $blazies->get('colorbox')
      && function_exists('colorbox_theme')) {
      $is_gallery = (bool) $manager->config('custom.slideshow.slideshow', 'colorbox.settings');
    }

    // Re-define based on potential hook_alter().
    if ($is_gallery) {
      $gallery_id = str_replace('_', '-', $gallery_id);
      $blazies->set('lightbox.gallery_id', $gallery_id)
        ->set('is.gallery', TRUE);
    }

    // Only needed for lightbox captions with entity label and tokens.
    if ($entity) {
      $blazies->set('entity.instance', $entity);
    }
  }

}
