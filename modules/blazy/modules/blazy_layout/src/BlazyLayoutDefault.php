<?php

namespace Drupal\blazy_layout;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\blazy\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 */
class BlazyLayoutDefault {

  /**
   * Defines region count.
   */
  const REGION_COUNT = 9;

  /**
   * Returns display style options, different from core Blazy for layouts.
   */
  public static function displayStyle() {
    return [
      'column' => 'CSS3 Columns',
      'grid' => 'Grid Foundation',
      'flexbox' => 'Flexbox',
      'nativegrid' => 'Native Grid',
    ];
  }

  /**
   * Returns sensible default options common for entities lacking of UI.
   */
  public static function entitySettings() {
    return BlazyDefault::entitySettings();
  }

  /**
   * Returns the layout settings.
   */
  public static function layoutSettings() {
    return [
      'id'             => '',
      'regions'        => [],
      'count'          => static::REGION_COUNT,
      'style'          => 'nativegrid',
      'grid'           => '4x4 4x3 2x2 2x4 2x2 2x3 2x3 4x2 4x2',
      'grid_medium'    => '3',
      'grid_small'     => '1',
      'grid_auto_rows' => '',
      'align_items'    => '',
    ] + self::sharedSettings();
  }

  /**
   * Returns the sub-layout settings.
   */
  public static function sublayoutSettings() {
    return [
      'ete'       => FALSE,
      'gapless'   => FALSE,
      'padding'   => '',
      'max_width' => '',
    ];
  }

  /**
   * Returns the media settings.
   */
  public static function layoutMediaSettings() {
    return [
      'id' => '',
      'background' => TRUE,
      'media_switch' => '',
      'image_style' => '',
      'responsive_image_style' => '',
      'box_caption' => '',
      'box_style' => '',
      'box_media_style' => '',
      'ratio' => 'fluid',
      'link' => '',
      // @todo remove after an update.
      'use_player' => FALSE,
    ];
  }

  /**
   * Returns the region layout settings.
   */
  public static function regionSettings() {
    return [
      'label'    => '',
      'settings' => self::sharedSettings(),
    ];
  }

  /**
   * Returns the region layout settings.
   */
  public static function styleSettings() {
    return [
      'background_color'   => '',
      'background_opacity' => '1',
      'overlay_color'      => '',
      'overlay_opacity'    => '1',
      'heading_color'      => '',
      'heading_opacity'    => '1',
      'text_color'         => '',
      'text_opacity'       => '1',
      'link_color'         => '',
      'link_hover_color'   => '',
    ];
  }

  /**
   * Returns align items options.
   */
  public static function aligItems() {
    return [
      'normal' => 'normal',
      'stretch' => 'stretch',
      'center' => 'center',
      'start' => 'start',
      'end' => 'end',
      'flex-start' => 'flex-start',
      'flex-end' => 'flex-end',
      'self-start' => 'self-start',
      'self-end' => 'self-end',
      'baseline' => 'baseline',
      'first baseline' => 'first baseline',
      'last baseline' => 'last baseline',
      'safe center' => 'safe center',
      'unsafe center' => 'unsafe center',
      'inherit' => 'inherit',
      'initial' => 'initial',
      'revert' => 'revert',
      'revert-layer' => 'revert-layer',
      'unset' => 'unset',
    ];
  }

  /**
   * Returns the main wrapper Layout Builder select options.
   */
  public static function mainWrapperOptions() {
    return [
      'div'     => 'Div',
      'article' => 'Article',
      'aside'   => 'Aside',
      'main'    => 'Main',
      'footer'  => 'Footer',
      'section' => 'Section',
    ];
  }

  /**
   * Returns wrapper Layout Builder select options.
   */
  public static function regionWrapperOptions() {
    return self::mainWrapperOptions() + [
      'figure' => 'Figure',
      'header' => 'Header',
    ];
  }

  /**
   * Returns layout id.
   */
  public static function layoutId($id) {
    return "b-layout--{$id}";
  }

  /**
   * Returns layout id.
   */
  public static function layoutLabel($label) {
    return "Blazy: {$label}";
  }

  /**
   * Returns region ID.
   */
  public static function regionId($id): string {
    return "blzyr_{$id}";
  }

  /**
   * Returns region label.
   */
  public static function regionLabel($id): string {
    return "Region {$id}";
  }

  /**
   * Returns region label.
   */
  public static function regionTranslatableLabel($label): TranslatableMarkup {
    return new TranslatableMarkup('@label', ['@label' => $label], [
      'context' => 'layout_region',
    ]);
  }

  /**
   * Returns the shared settings.
   */
  public static function sharedSettings() {
    return [
      'wrapper'     => 'div',
      'attributes'  => '',
      'classes'     => '',
      'row_classes' => '',
      'styles'      => [
        'colors'  => self::styleSettings(),
        'layouts' => self::sublayoutSettings(),
        'media' => self::layoutMediaSettings(),
      ],
    ];
  }

}
