<?php

namespace Drupal\az_paragraphs;

use Drupal\file\Entity\File;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;

/**
 * Class AZResponsiveBackgroundCSSFormatter. Adds service to create responsive background image css.
 */
class AZResponsiveBackgroundCSSFormatter {

  /**
   * Drupal\Core\Asset\LibraryDiscoveryInterface definition.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * {@inheritdoc}
   */
  public static function __construct() {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    return $instance;
  }


  /**
   * The file entity.
   *
   * @var \Drupal\file\Entity\File
   */
  protected $file;


  /**
   * Get CSS that conforms to an install breakpoint set.
   *
   * @param array $settings
   *   Settings for the background image.
   * @param Entity $image
   *   The the media entity.
   * @param array $langcode
   *   The image language settings.
   *
   * @return mixed
   *   String Youtube video ID or boolean FALSE if not found.
   */
  // public function getResponsiveBackgroundImageCSS(array $settings, File $image) {
  //   $this->file = $image;
  //   $elements = [];
  //   $index = 0;
  //   $css_settings = $settings['css_settings'];
  //   $selectors = array_filter(preg_split('/$/', $css_settings['bg_image_selector']));

  //   // Filter out empty selectors.
  //   $selectors = array_map(static function ($value) {
  //     return trim($value, ',');
  //   }, $selectors);

  //   // Early opt-out if the field is empty.
  //   if (empty($image) || empty($settings['image_style'])) {
  //     return $elements;
  //   }

  //   // Prepare token data in bg image css selector.
  //   $token_data = [
  //     'user' => \Drupal::currentUser(),
  //     $image->getEntityTypeId() => $image,
  //   ];

  //   foreach ($selectors as &$selector) {
  //     $selector = \Drupal::token()->replace($selector, $token_data);
  //   }

  //   // Need an empty element so views renderer will see something to render.
  //   $elements[0] = [];

  //     // Use specified selectors in round-robin order.
  //     $selector = $selectors[$index % \count($selectors)];
  //     $vars = [
  //       'uri' => $image->getFileUri(),
  //       'responsive_image_style_id' => $settings['image_style'],
  //     ];
  //     template_preprocess_responsive_image($vars);

  //   //   if (empty($vars['sources'])) {
  //   //     continue;
  //   //   }
  //     // Split each source into multiple rules.
  //     foreach (array_reverse($vars['sources']) as $source_i => $source) {
  //       $attr = $source->toArray();

  //       $srcset = explode(', ', $attr['srcset']);

  //       foreach ($srcset as $src_i => $src) {
  //         list($src, $res) = explode(' ', $src);

  //         $media = isset($attr['media']) ? $attr['media'] : '';

  //         // Add "retina" to media query if this is a 2x image.
  //         if ($res && $res === '2x' && !empty($media)) {
  //           $media = "{$media} and (-webkit-min-device-pixel-ratio: 2), {$media} and (min-resolution: 192dpi)";
  //         }

  //         // Correct a bug in template_preprocess_responsive_image which
  //         // generates an invalid media rule "screen (max-width)" when no
  //         // min-width is specified. If this bug gets fixed, this replacement
  //         // will deactivate.
  //         $media = str_replace('screen (max-width', 'screen and (max-width', $media);

  //         $css_settings['bg_image_selector'] = $selector;

  //         $css = $this->getBackgroundImageCss($src, $css_settings);

  //         // $css_settings['bg_image_selector'] probably needs to be sanitized.
  //         $with_media_query = sprintf('%s { background-image: url(%s);}', $css_settings['bg_image_selector'], $vars['img_element']['#uri']);

  //         $with_media_query .= sprintf('@media %s {', $media);
  //         $with_media_query .= sprintf($css['data']);
  //         $with_media_query .= '}';

  //         $css['attributes']['media'] = $media;
  //         $css['data'] = $with_media_query;

  //       //   dpm($css);

  //       //   $style_elements = [
  //       //     'style' => [
  //       //       '#type' => 'inline_template',
  //       //       '#template' => "{{ css }}",
  //       //       '#context' => [
  //       //         'css' => Markup::create($css['data']),
  //       //       ],
  //       //       '#attributes' => [
  //       //         'media' => $css['attributes']['media'],
  //       //       ],
  //       //     ],
  //       //   ];
  //       //   $style_element = [
  //       //     '#type' => 'html_tag',
  //       //     '#tag' => 'style',
  //       //     '#attributes' => [
  //       //       'media' => $css['attributes']['media'],
  //       //     ],
  //       //     '#value' => Markup::create($css['data']),
  //       //   ];
  //       //   $elements['#attached']['css'][] = '$css';
  //       // $elements['#attached']['css'][] = $css;
  //       $style_elements[] = [
  //           'style' => [
  //             '#type' => 'inline_template',
  //             '#template' => "{{ css }}",
  //             '#context' => [
  //               'css' => Markup::create($css['data']),
  //             ],
  //             '#attributes' => [
  //               'media' => $css['attributes']['media'],
  //             ],
  //           ],
  //         ];
  //       }
  //     }


  //   return $style_elements;
  // }


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
    // $defaults = self::defaultSettings();
    // $css_settings += $defaults['css_settings'];

    // Pull the default css setting if not provided.
    $selector = Xss::filter($css_settings['bg_image_selector']);
    $bg_color = Xss::filter($css_settings['bg_image_color']);
    $bg_x = Xss::filter($css_settings['bg_image_x']);
    $bg_y = Xss::filter($css_settings['bg_image_y']);
    $attachment = $css_settings['bg_image_attachment'];
    $repeat = $css_settings['bg_image_repeat'];
    $important = $css_settings['bg_image_important'];
    $background_size = Xss::filter($css_settings['bg_image_background_size']);
    $background_size_ie8 = $css_settings['bg_image_background_size_ie8'];
    $background_gradient = !empty($css_settings['bg_image_gradient']) ? $css_settings['bg_image_gradient'] . ',' : '';
    $media_query = isset($css_settings['bg_image_media_query']) ? Xss::filter($css_settings['bg_image_media_query']) : NULL;
    $z_index = Xss::filter($css_settings['bg_image_z_index']);

    // If important is true, we turn it into a string for css output.
    if ($important) {
      $important = '!important';
    }
    else {
      $important = '';
    }

    // Handle the background size property.
    $bg_size = '';
    $ie_bg_size = '';

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
