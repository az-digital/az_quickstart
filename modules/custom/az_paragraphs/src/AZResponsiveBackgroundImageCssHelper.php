<?php

namespace Drupal\az_paragraphs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;

/**
 * Class AZResponsiveBackgroundImageCssHelper.
 *
 * Adds service to form responsive background image CSS.
 */
class AZResponsiveBackgroundImageCssHelper {

  /**
   * The background image css service.
   *
   * @var \Drupal\az_paragraphs\AZBackgroundImageCssHelper
   */
  protected $backgroundImageCss;

  /**
   * Constructs an AZResponsiveBackgroundImageCssHelper.
   *
   * @param \Drupal\az_paragraphs\AZBackgroundImageCssHelper $backgroundImageCss
   *   The background image css helper service.
   */
  public function __construct(
    AZBackgroundImageCssHelper $backgroundImageCss
    ) {
    $this->backgroundImageCss = $backgroundImageCss;
  }

  /**
   * Adds a responsive background image to the page using the
   * css 'background' property.
   *
   * @param \Drupal\Core\Entity\EntityInterface $image
   *   The entity to display.
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
   * @param string $responsive_image_style
   *   Add responsive image style to the image before applying it to the
   *   background.
   *
   * @return array
   *   The array containing the CSS.
   */
  public function getResponsiveBackgroundImageCss(EntityInterface $image, array $css_settings = [], $responsive_image_style = NULL) {

    $style_elements = [];

    $selector = HTML::getId($css_settings['bg_image_selector']);
    $vars = [
      'uri' => $image->getFileUri(),
      'responsive_image_style_id' => $responsive_image_style,
    ];

    template_preprocess_responsive_image($vars);

    // Split each source into multiple rules.
    foreach (array_reverse($vars['sources']) as $source_i => $source) {
      $attr = $source->toArray();

      $srcset = explode(', ', $attr['srcset']);

      foreach ($srcset as $src_i => $src) {
        list($src, $res) = explode(' ', $src);

        $media = isset($attr['media']) ? $attr['media'] : '';

        // Add "retina" to media query if this is a 2x image.
        if ($res && $res === '2x' && !empty($media)) {
          $media = "{$media} and (-webkit-min-device-pixel-ratio: 2), {$media} and (min-resolution: 192dpi)";
        }

        // Correct a bug in template_preprocess_responsive_image which
        // generates an invalid media rule "screen (max-width)" when no
        // min-width is specified. If this bug gets fixed, this replacement
        // will deactivate.
        $media = str_replace('screen (max-width', 'screen and (max-width', $media);

        $css = $this->backgroundImageCss->getBackgroundImageCss($src, $css_settings);

        $with_media_query = sprintf('%s { background-image: url(%s);}', $selector, $vars['img_element']['#uri']);

        $with_media_query .= sprintf('@media %s {', $media);
        $with_media_query .= sprintf($css['data']);
        $with_media_query .= '}';

        $css['attributes']['media'] = $media;
        $css['data'] = $with_media_query;

        $style_elements[] = [
          'style' => [
            '#type' => 'inline_template',
            '#template' => "{{ css }}",
            '#context' => [
              'css' => Markup::create($css['data']),
            ],
            '#attributes' => [
              'media' => $css['attributes']['media'],
            ],
          ],
        ];
      }
    }
    return $style_elements;
  }

}
