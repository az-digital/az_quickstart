<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick module.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Core\Template\Attribute;
use Drupal\slick\Entity\Slick;
use Drupal\slick\SlickDefault as Defaults;

/**
 * Prepares variables for slick.html.twig templates.
 */
function template_preprocess_slick(&$variables) {
  $element = $variables['element'];

  // Pre 2.10 only used variables were added, now many in case needed.
  foreach (Defaults::themeProperties() as $key => $default) {
    // A special variables#attached is taken care of upstream, not here.
    // Even if needed it should be $variables["#$key"] due to being special.
    // You can always add it even if skipped here to avoid dups. Core thingies
    // like user, etc. They are there/ respected even if not declared here.
    // See https://www.drupal.org/project/drupal/issues/2346369.
    if ($key == 'attached') {
      continue;
    }
    $variables[$key] = $element["#$key"] ?? $default;
  }

  // Excluded as this was hardly used/ elaborated upstream.
  $key = 'content_attributes';
  $variables[$key] = $element["#$key"] ?? new Attribute();

  // Shortcuts.
  $sets = &$variables['settings'];
  slick()->verifySafely($sets);

  $blazies   = $sets['blazies'];
  $slicks    = $sets['slicks'];
  $optionset = Slick::verifyOptionset($variables, $sets['optionset']);
  $js        = &$variables['options'];
  $js       += $optionset->getSettings();
  $display   = $variables['display'] = $sets['display'];
  $id        = $blazies->get('css.id', $sets['id'] ?? NULL);
  $id        = $sets['id'] = slick()->getHtmlId('slick', $id);
  $real_id   = $display == 'thumbnail' ? $id . '-nav' : $id;

  // Prepare attributes, since Twig prepends, not appends, causing BEM problems.
  $attrs          = &$variables['attributes'];
  $content_attrs  = &$variables['content_attributes'];
  $classes        = (array) ($attrs['class'] ?? []);
  $attrs['class'] = array_merge(['slick'], $classes);

  // @see SlickManager::buildGrid(), and this should make sense.
  $sets['count']   = $count = $blazies->get('count') ?: count($element['#items']);
  $sets['unslick'] = $unslick = $slicks->is('unslick') || $count == 1;

  // Re-define for sure.
  $slicks->set('is.unslick', $unslick);
  $blazies->set('count', $count);

  // More shortcuts for convenience at Twig.
  foreach (Defaults::jsSettings() as $key => $default) {
    $value = $js[$key] ?? $default;
    $sets[$key] = is_string($value) ? strip_tags($value) : $value;
  }

  // Blazy can still lazyload an unslick.
  // The lazy supercedes JS lazyLoad for background, breakpoints, media, etc.
  if ($display != 'thumbnail') {
    $js['lazyLoad'] = 'blazy';
  }

  // Make slick language-direction-aware.
  $language = \Drupal::languageManager()->getCurrentLanguage();
  if ($language->getDirection() == 'rtl') {
    $attrs['dir'] = $language->getDirection();
    $js['rtl'] = TRUE;
  }

  // Remove settings that aren't supported by the active library.
  Slick::removeUnsupportedSettings($js);

  // Prevents broken slick when only one item given, or an enforced unslick.
  if (!$slicks->is('unslick')) {
    $content_attrs->setAttribute('id', $real_id);

    $variables['arrow_attributes'] = new Attribute();
    $variables['arrow_attributes']['role'] = 'navigation';
    if ($display == 'main' && !empty($js['downArrow']) && !empty($js['downArrowTarget'])) {
      $variables['arrow_down_attributes'] = new Attribute();
    }

    // focusOnSelect won't work with empty slide value, so add proper selector.
    // Respects core Grid markups which may wrap .slick__slide within anon DIV.
    // Unfortunately focusOnSelect is removed by Accessible Slick. We kept it
    // here for just in case it will be re-enacted due to being overlooked.
    if (empty($js['slide']) && !empty($js['focusOnSelect'])) {
      // Different versions have different logic causing breaking changes.
      // Basically the fundamental issue: `rows > 0` vs `rows > 1`. This is
      // enough to break slides with duplicate slide markups + anonymous DIV.
      // What we do here is to accommodate both versions.
      $add = ($js['rows'] < 2 && $js['slidesPerRow'] < 2);

      // Fix for breaking changes with Slick 1.8.1/1.9.0 and Accessible Slick.
      // This is no use for Accessible Slick due to no-synching w/ thumbnail.
      if (!empty($sets['breaking'])) {
        $add = $js['rows'] < 2;
      }
      $js['slide'] = $add ? '.slick__slide' : $js['slide'];
    }

    // Add the configuration as JSON object into the slick container.
    $js = array_merge($js, (array) ($variables['js'] ?? []));
    if ($json = $optionset->toJson($js)) {
      $content_attrs->setAttribute('data-slick', Json::encode($json));
    }
  }

  // Shortcuts for easy calls.
  $variables['blazies'] = $blazies->storage();
  $variables['slicks'] = $slicks->storage();

  // @todo remove for variables.options at 3.x, just an unneeded oversight.
  // Splide has done it correctly learning from this mistake from the start.
  // If you are using this, consider replacing it with variables.options.
  $variables['js'] = $js;
  $variables['js']['WARNING'] = t('deprecated at slick:2.10 and is removed from slick:3.x. Use variable.options instead.');

  // Process or interpolate individual item.
  foreach ($variables['items'] as $delta => $item) {
    if (!is_array($item)) {
      continue;
    }

    $item_sets = slick()->toHashtag($item);
    $item_sets += $sets;
    $item_attrs = slick()->toHashtag($item, 'attributes');
    $item_content_attrs = slick()->toHashtag($item, 'content_attributes');
    $item_sets['current_item'] = $display;

    // @todo refactor for hashed keys at 3.x to avoid this in the first place.
    // Was meant to be conveniences as these are elaborated much before
    // arriving here, but might cause render array troubles to those unaware of.
    // Otherwise leave them as they are for good. A good exercise nonetheless.
    unset(
      $item['settings'],
      $item['attributes'],
      $item['content_attributes'],
      $item['item']
    );

    // These themes are the only reason why we did this the way it is.
    // Kind of control room here to facilitate various item themes at one pass.
    $theme = $sets['vanilla'] ? 'vanilla' : ($display == 'thumbnail' ? 'thumbnail' : 'slide');
    $content = $item;
    unset($content['#entity'], $content['#cache']);

    $slide = [
      '#theme' => 'slick_' . $theme,
      // @todo rename item to content for core convention at 3.x.
      '#item' => $content,
      '#delta' => $delta,
      '#settings' => $item_sets,
      '#attributes' => $item_attrs,
      '#content_attributes' => $item_content_attrs,
    ];

    foreach (['cache', 'entity'] as $key) {
      if ($value = ($item["#$key"]) ?? NULL) {
        $slide["#$key"] = $value;
      }
    }

    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for slick-wrapper.html.twig templates.
 */
function template_preprocess_slick_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = $variables['element']["#$key"] ?? [];
  }

  // Shortcuts for easy calls.
  $sets = &$variables['settings'];
  slick()->verifySafely($sets);

  $variables['blazies'] = $sets['blazies']->storage();
  $variables['slicks'] = $sets['slicks']->storage();
}

/**
 * Prepares common variables for slick item templates.
 */
function _slick_preprocess_slick_item(&$variables) {
  $element = $variables['element'];
  foreach (['attributes', 'content_attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? 0 : [];
    $variables[$key] = $element["#$key"] ?? $default;
  }

  // Because Twig prepends new classes, not appends as expected by BEM.
  $attrs = &$variables['attributes'];
  $classes = (array) ($attrs['class'] ?? []);
  $attrs['class'] = array_merge(['slick__slide', 'slide'], $classes);

  // Shortcuts for easy calls.
  $sets = $variables['settings'];
  $variables['blazies'] = $sets['blazies']->storage();
  $variables['slicks'] = $sets['slicks']->storage();
}

/**
 * Prepares variables for slick-vanilla.html.twig templates.
 */
function template_preprocess_slick_vanilla(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-thumbnail.html.twig templates.
 */
function template_preprocess_slick_thumbnail(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-slide.html.twig templates.
 */
function template_preprocess_slick_slide(&$variables) {
  _slick_preprocess_slick_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = $variables['item']['caption'][$key] ?? [];
  }

  $item    = &$variables['item'];
  $sets    = &$variables['settings'];
  $blazies = $sets['blazies'];

  $variables['caption_attributes'] = new Attribute();

  // @todo remove the first check at 3.x.
  if ($blazies->use('theme_blazy')
    && $attrs = $blazies->get('item.attributes', [])) {
    $variables['attributes'] = slick()->merge($variables['attributes'], $attrs);
  }

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unslick grid.
  // wrapper: Don't add divities for a single item to have clean markups.
  $unslick           = !empty($sets['unslick']);
  $item['slide']     = $slide = $item['slide'] ?? [];
  $item['caption']   = $caption = array_filter($item['caption']);
  $sets['split']     = $slide && !$unslick && (!empty($sets['caption']) || !empty($sets['title']));
  $sets['data']      = !empty($caption['alt']) || !empty($caption['title']) || !empty($caption['data']);
  $sets['fullwidth'] = !empty($sets['skin']) && strpos($sets['skin'], 'full') !== FALSE;
  $sets['grid']      = empty($sets['grid']) ? FALSE : $sets['grid'];
  $sets['detroy']    = $sets['current_item'] == 'main' && !empty($sets['grid']) && !empty($sets['unslick']);
  $sets['wrapper']   = $sets['count'] > 1 && $sets['current_item'] != 'grid';
  $sets['use_ca']    = $sets['wrapper'] && (empty($sets['grid']) || !empty($variables['content_attributes']));
}

/**
 * Prepares variables for slick-grid.html.twig templates.
 *
 * Slick now uses core theme_item_list via Blazy::grid instead. This function is
 * no longer functional, nor called. Should you need to modify its output, use
 * hook_slick_grid_item_alter instead, see SlickManager::buildGridItem().
 * This is still kept to avoid WSOD during careless update.
 *
 * @deprecated in slick:8.x-2.3 and is removed from slick:8.x-3.0. Use
 *   hook_slick_grid_item_alter if necessary instead.
 * @see https://www.drupal.org/node/3239708
 */
function template_preprocess_slick_grid(&$variables) {
  $element                 = $variables['element'];
  $variables['settings']   = $sets = $element['#settings'];
  $variables['delta']      = $element['#delta'] ?? 0;
  $variables['grid_id']    = 'grid';
  $variables['attributes'] = $variables['attributes'] ?? [];

  $variables['items'] = [];
  foreach ($variables['element']['#items'] as $delta => $item) {
    $slide = ['content' => [], 'attributes' => []];
    $variables['items'][$delta] = $slide;
  }
}
