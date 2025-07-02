<?php

namespace Drupal\ib_dam\AssetFormatter;

/**
 * Class AssetFeatures.
 *
 * Helper class to avoid code duplication between embed formatters,
 * and local one.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
class AssetFeatures {

  /**
   * Get settings form elements for playable formatter types.
   *
   * @param array $settings
   *   The default settings get from asset data.
   *
   * @return array
   *   The array of form elements.
   */
  public static function getPlayableSettings(array $settings = []) {
    return [
      'controls' => [
        '#title' => t('Show playback controls'),
        '#type' => 'checkbox',
        '#default_value' => isset($settings['controls']) ? $settings['controls'] : TRUE,
      ],
      'autoplay' => [
        '#title' => t('Autoplay'),
        '#type' => 'checkbox',
        '#default_value' => isset($settings['autoplay']) ? $settings['autoplay'] : FALSE,
      ],
      'loop' => [
        '#title' => t('Loop'),
        '#type' => 'checkbox',
        '#default_value' => isset($settings['loop']) ? $settings['loop'] : FALSE,
      ],
    ];
  }

  /**
   * Get settings form elements for viewable formatter types.
   *
   * @param array $settings
   *   The default settings get from asset data.
   *
   * @return array
   *   The array of form elements.
   */
  public static function getViewableSettings(array $settings = []) {
    return [
      'width' => [
        '#title' => t('Display Width'),
        '#type' => 'number',
        '#description' => t('Customise an HTML Width attribute. To resize a source image use Remote URL option below.<br>Leave empty to use original size.'),
        '#size' => 4,
        '#maxlength' => 7,
      ],
      'height' => [
        '#title' => t('Display Height'),
        '#type' => 'number',
        '#description' => t('Customise an HTML Height attribute. To resize a source image use Remote URL option below.<br>Leave empty to use original size.'),
        '#size' => 4,
        '#maxlength' => 7,
      ],
    ];
  }

  /**
   * Get settings form elements for caption based formatter types.
   *
   * @param array $settings
   *   The default settings get from asset data.
   *
   * @return array
   *   The array of form elements.
   */
  public static function getCaptionSettings(array $settings = []) {
    return [
      'alt' => [
        '#type' => 'textfield',
        '#title' => t('Alternative text'),
        '#maxlenght' => 255,
        '#default_value' => isset($settings['alt']) ? $settings['alt'] : '',
      ],
      'title' => [
        '#type' => 'textfield',
        '#title' => t('Title'),
        '#maxlenght' => 255,
        '#default_value' => isset($settings['title']) ? $settings['title'] : '',
      ],
    ];
  }

  /**
   * Get settings form elements for description based formatter types.
   *
   * @param array $settings
   *   The default settings get from asset data.
   *
   * @return array
   *   The array of form elements.
   */
  public static function getDescriptionSettings(array $settings = []) {
    return [
      'alt' => [
        '#type' => 'textfield',
        '#title' => t('Description text'),
        '#maxlenght' => 255,
        '#default_value' => isset($settings['alt']) ? $settings['alt'] : '',
      ],
    ];
  }

}
