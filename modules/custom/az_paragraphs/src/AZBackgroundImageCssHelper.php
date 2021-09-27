<?php

namespace Drupal\az_paragraphs;

use Drupal\Component\Utility\Xss;

/**
 * Class AZBackgroundImageCssHelper. Adds service to form background image CSS.
 */
class AZBackgroundImageCssHelper {

  /**
   * {@inheritdoc}
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
        'bg_image_background_size' => '',
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

    $selector = Xss::filter($css_settings['bg_image_selector']);
    $bg_color = Xss::filter($css_settings['bg_image_color']);
    $bg_x = Xss::filter($css_settings['bg_image_x']);
    $bg_y = Xss::filter($css_settings['bg_image_y']);
    $attachment = $css_settings['bg_image_attachment'];
    $repeat = $css_settings['bg_image_repeat'];
    $important_set = $css_settings['bg_image_important'];
    $background_size = Xss::filter($css_settings['bg_image_background_size']);
    $background_size_ie8 = $css_settings['bg_image_background_size_ie8'];
    $background_gradient = !empty($css_settings['bg_image_gradient']) ? $css_settings['bg_image_gradient'] . ',' : '';
    $media_query = isset($css_settings['bg_image_media_query']) ? Xss::filter($css_settings['bg_image_media_query']) : NULL;
    $z_index = Xss::filter($css_settings['bg_image_z_index']);
    $important = 0;
    $bg_size = '';
    $ie_bg_size = '';

    // If important_set is true, we turn it into a string for css output.
    if ($important_set) {
      $important = '!important';
    }

    // Handle the background size property.
    if ($background_size) {
      // CSS3.
      $bg_size = sprintf('background-size: %s %s;', $background_size, $important);
      // Let's cover ourselves for other browsers as well...
      $bg_size .= sprintf('-webkit-background-size: %s %s;', $background_size, $important);
      $bg_size .= sprintf('-moz-background-size: %s %s;', $background_size, $important);
      $bg_size .= sprintf('-o-background-size: %s %s;', $background_size, $important);
      // IE filters to apply the cover effect.
      if ($background_size === 'cover' && $background_size_ie8) {
        $ie_bg_size = sprintf(
          "filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='%s', sizingMethod='scale');",
          $image_path
        );
        $ie_bg_size .= sprintf(
          "-ms-filter: progid:DXImageTransform.Microsoft.AlphaImageLoader(src='%s', sizingMethod='scale');",
          $image_path
        );
      }
    }

    // Add the css if we have everything we need.
    if ($selector && $image_path) {
      $style = sprintf('%s {', $selector);

      if ($bg_color) {
        $style .= sprintf('background-color: %s %s;', $bg_color, $important);
      }
      $style .= sprintf("background-image: %s url('%s') %s;", $background_gradient, $image_path, $important);

      if ($repeat) {
        $style .= sprintf('background-repeat: %s %s;', $repeat, $important);
      }

      if ($attachment) {
        $style .= sprintf('background-attachment: %s %s;', $attachment, $important);
      }

      if ($bg_x && $bg_y) {
        $style .= sprintf('background-position: %s %s %s;', $bg_x, $bg_y, $important);
      }

      if ($z_index) {
        $style .= sprintf('z-index: %s;', $z_index);
      }
      $style .= $bg_size;
      $style .= $background_size_ie8 ? $ie_bg_size : '';
      $style .= '}';

      return [
        'data' => $style,
        'media' => !empty($media_query) ? $media_query : 'all',
        'group' => CSS_THEME,
      ];
    }

    return [];
  }

}
