<?php

namespace Drupal\az_paragraphs;

use Drupal\Component\Render\FormattableMarkup;

/**
 * Class AZBackgroundImageCssHelper. Adds service to form background image CSS.
 */
class AZBackgroundImageCssHelper {

  /**
   * @return array
   *   An array with all required settings.
   */
  public static function defaultSettings() {
    return [
      'css_settings' => [
        'bg_image_selector' => 'body',
        'bg_image_color' => '#FFFFFF',
        'bg_image_x' => 'left',
        'bg_image_y' => 'top',
        'bg_image_attachment' => 'scroll',
        'bg_image_repeat' => 'no-repeat',
        'bg_image_background_size' => 'cover',
        'bg_image_background_size_ie8' => 0,
        'bg_image_gradient' => '',
        'bg_image_media_query' => 'all',
        'bg_image_important' => 0,
        'bg_image_z_index' => 'auto',
        'bg_image_path_format' => 'absolute',
      ],
    ];
  }

  /**
   * Function taken from the module 'bg_image'.
   *
   * Adds a background image to the page using the
   * css 'background' property.
   *
   * @param string $image_path
   *   The path of the image to use. This can be either
   *      - A relative path e.g. sites/default/files/image.png
   *      - A uri: e.g. public://image.png.
   * @param array $css_settings
   *   An array of css settings to use. Possible values are:
   *      - bg_image_selector: The css selector to use
   *      - bg_image_color: The background color
   *      - bg_image_x: The x offset
   *      - bg_image_y: The y offset
   *      - bg_image_attachment: The attachment property (scroll or fixed)
   *      - bg_image_repeat: The repeat settings
   *      - bg_image_background_size: The background size property if necessary
   *    Default settings will be used for any values not provided.
   * @param string $image_style
   *   Optionally add an image style to the image before applying it to the
   *   background.
   *
   * @return array
   *   The array containing the CSS.
   */
  public function getBackgroundImageCss($image_path, array $css_settings = [], $image_style = NULL) {

    // Pull the default css setting if not provided.
    $defaults = self::defaultSettings();
    // Merge defaults into css_settings array without overriding values.
    $css_settings += $defaults['css_settings'];

    // Pull the default css setting if not provided.
    $selector = $css_settings['bg_image_selector'];
    $bg_color = $css_settings['bg_image_color'];
    $bg_x = $css_settings['bg_image_x'];
    $bg_y = $css_settings['bg_image_y'];
    $attachment = $css_settings['bg_image_attachment'];
    $repeat = $css_settings['bg_image_repeat'];
    $important_set = $css_settings['bg_image_important'];
    $important = '';
    $background_size = $css_settings['bg_image_background_size'];
    $background_gradient = !empty($css_settings['bg_image_gradient']) ? $css_settings['bg_image_gradient'] . ',' : '';
    $media_query = isset($css_settings['bg_image_media_query']) ? $css_settings['bg_image_media_query'] : NULL;
    $z_index = $css_settings['bg_image_z_index'];

    // Handle the background size property.
    $bg_size = '';

    // If important_set is true, we turn it into a string for css output.
    if ($important_set) {
      $important = '!important';
    }

    // Handle the background size property.
    if ($background_size) {
      // CSS3.
      $bg_size = new FormattableMarkup(
        'background-size: :bg_size :important;',
        '-webkit-background-size: :bg_size :important;',
        '-moz-background-size: :bg_size :important;',
        '-o-background-size: :bg_size :important;', [
          ':bg_size' => $background_size,
          ':important' => $important,
        ]
      );
    }

    // Add the css if we have everything we need.
    if ($selector && $image_path) {
      $style = new FormattableMarkup(
        ':selector {', [
          ':selector' => $selector,
        ]
      );
      if ($attachment) {
        $style .= new FormattableMarkup(
          ' background-attachment: :attachment :important;', [
            ':attachment' => $attachment,
            ':important' => $important,
          ]
        );

      }
      if ($bg_color) {
        $style .= new FormattableMarkup(
          ' background-color: :bg_color :important;', [
            ':bg_color' => $bg_color,
            ':important' => $important,
          ]
        );
        $style .= $style_background_color;
      }

      $background_image_style = new FormattableMarkup(
        'background-image: :bg_gradient url(":image_path") :important;', [
          ':image_path' => $image_path,
          ':bg_gradient' => $background_gradient,
          ':important' => $important,
        ]
      );
      $style .= $background_image_style;
      if (!empty($repeat)) {
        $style .= new FormattableMarkup(
          ' background-repeat: :repeat :important;', [
            ':repeat' => $repeat,
            ':important' => $important,
          ]
        );
      }
      if ($z_index) {
        $style .= new FormattableMarkup(
          ' z-index: :z_index;', [
            ':z_index' => $z_index,
          ]
        );
      }

      $style .= $bg_size . '}';

      return [
        'data' => $style,
        'media' => !empty($media_query) ? $media_query : 'all',
        'group' => CSS_THEME,
      ];
    }

    return [];
  }

}
