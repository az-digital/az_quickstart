<?php

namespace Drupal\blazy\Media;

use Drupal\blazy\Theme\Attributes;
use Drupal\blazy\internals\Internals;

/**
 * Provides placeholder thumbnail image.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 *
 * @todo recap similiraties and make them plugins.
 */
class Placeholder {

  /**
   * Defines constant placeholder  blank URL.
   */
  const BLANK = 'about:blank';

  /**
   * Defines constant placeholder Data URI image.
   *
   * <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"/>
   */
  const DATA = "data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg'%20viewBox%3D'0%200%201%201'%2F%3E";

  /**
   * Defines constant placeholder Data URI image.
   */
  const GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * Build out the blur image.
   *
   * Provides image effect if so configured unless being sandboxed.
   * Being a separated .b-blur with .b-lazy, this should work for any lazy.
   * Ensures at least a hook_alter is always respected. This still allows
   * Blur and hook_alter for Views rewrite issues, unless global UI is set
   * which was already warned about anyway.
   *
   * Since 2.10, using client-size solution, too many bytes for a short life.
   */
  public static function blur(array &$variables, array &$settings) {
    $attributes = &$variables['attributes'];
    $blazies = $settings['blazies'];
    $uri = $blazies->get('blur.uri');
    $url = $blazies->get('blur.url');

    if (!$url) {
      return;
    }

    // Suppress useless warning of likely failing initial image generation.
    // Better than checking file exists.
    $mime = @mime_content_type($uri);
    $id = md5($url);
    $client = $blazies->ui('blur_client');
    $store = $client ? ($blazies->ui('blur_storage') ? 1 : 0) : -1;

    // If blur and thumbnail use the same image style, indicate so instead to
    // save from few bytes.
    if ($url == $blazies->get('thumbnail.url')) {
      $url = Attributes::data($blazies, 'thumb');
    }

    $dimensions = [];
    $width = (int) $blazies->get('placeholder.width', 0);
    if ($width > 1) {
      $dimensions['#width'] = $width;
      $dimensions['#height'] = $blazies->get('placeholder.height');
    }

    $blur = [
      '#theme' => 'image',
      '#uri' => $blazies->get('placeholder.url'),
      '#attributes' => [
        'alt' => t('Preview'),
        'class' => ['b-blur'],
        'data-b-blur' => "$store::$id::$mime::$url",
        'decoding' => 'async',
      ],
    ] + $dimensions;

    // Preserves old behaviors.
    if ($client) {
      $attributes['class'][] = 'is-blur-client';
    }
    else {
      $blur['#attributes']['class'][] = 'b-lazy';
      $blur['#attributes']['data-src'] = $blazies->get('blur.data');
    }

    $width = (int) $blazies->get('image.width', 0);
    if ($width > 980) {
      $attributes['class'][] = 'media--fx-lg';
    }

    // Reset as already stored.
    $blazies->set('blur.data', '');
    $variables['preface']['blur'] = $blur;
  }

  /**
   * Generates an SVG Placeholder.
   *
   * @param string|int $width
   *   The image width.
   * @param string|int $height
   *   The image height.
   *
   * @return string
   *   Returns a string containing an SVG.
   */
  public static function generate($width = 100, $height = 100): string {
    $width = $width ?: 100;
    $height = $height ?: 100;
    return 'data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\'%20viewBox%3D\'0%200%20' . $width . '%20' . $height . '\'%2F%3E';
  }

  /**
   * Build thumbnails, also to provide placeholder for blur effect.
   *
   * Requires image style and dimensions setup after BlazyImage::prepare().
   * The `[data-b-thumb|data-thumb(deprecated)]` attribute usages:
   * - Zoom-in-out effect as seen at Splidebox and PhotoSwipe.
   * - Hoverable or static grid pagination/ thumbnails seen at Splide/ Slick.
   * - Lightbox thumbnails seen at Splidebox.
   * - Switchable thumbnail to main stage seen at ElevateZoomPlus.
   * - Slider arrows with thumbnails as navigation previews, etc. seen at Slick.
   * - etc.
   *
   * The `[data-b-animation|data-animation(deprecated)]` attribute usages:
   * - Blur animation.
   * - Any animation supported by `animate.css` as seen GridStack, or custom.
   *   Check out `/admin/help/blazy_ui` for details.
   *
   * Most of these had been implemented since 1.x.
   *
   * @see \Drupal\blazy\Blazy:prepared()
   * @see self:blurs()
   * @see self:thumbnails()
   */
  public static function prepare(array &$attributes, array &$settings) {
    // Requires dimensions and image style setup.
    self::blurs($settings);
    self::thumbnails($settings);

    // Apply attributes related to Blur and Thumbnail image style.
    $blazies = $settings['blazies'];
    if ($url = $blazies->get('thumbnail.url')) {
      $attributes[Attributes::data($blazies, 'thumb')] = $url;
    }

    // Provides image effect if so configured unless being sandboxed.
    // Slick/ Splide lazy loads won't work, needs Blazy to make animation.
    if ($fx = $blazies->get('fx')) {
      $attributes['class'][] = 'media--fx';
      $attributes[Attributes::data($blazies, 'animation')] = $fx;
    }
  }

  /**
   * Checks for blur settings, required Image style and dimensions setup.
   */
  private static function blurs(array &$settings): void {
    $blazies = $settings['blazies'];
    if (!$blazies->use('blur')) {
      return;
    }

    // Disable Blur if the image style width is less than Blur min-width.
    if ($minwidth = (int) $blazies->ui('blur_minwidth', 0)) {
      $width = (int) $blazies->get('image.width');
      if ($width < $minwidth) {
        // Ensures ony if Blur since animation can be anything.
        if ($blazies->get('fx') == 'blur') {
          $blazies->set('fx', NULL);
        }

        $blazies->set('is.blur', FALSE);
      }
    }
  }

  /**
   * Provide `data:image` placeholder for blur effect.
   *
   * Ensures at least a hook_alter is always respected. This still allows
   * Blur and hook_alter for Views rewrite issues, unless global UI is set
   * which was already warned about anyway.
   */
  private static function dataImage(array &$settings, $uri, $tn_uri, $tn_url, $style): void {
    $blazies = $settings['blazies'];
    if (!$blazies->use('blur')) {
      return;
    }

    // Provides default path, in case required by global, but not provided.
    if ($manager = Internals::service('blazy.manager')) {
      $style = $style ?: $manager->load('thumbnail', 'image_style');
    }

    if (empty($tn_uri) && $style && BlazyFile::isValidUri($uri)) {
      $options['unsafe'] = FALSE;
      $tn_uri = $style->buildUri($uri);
      $tn_url = BlazyImage::url($uri, $style, $options);
    }

    // Overrides placeholder with data URI based on configured thumbnail.
    $valid = self::derivative($blazies, $uri, $tn_uri, $style, 'blur');
    if ($valid) {
      // Use client-side for better DOM diet.
      if (!$blazies->ui('blur_client')
        && $content = file_get_contents($tn_uri)) {
        $blur = 'data:image/' .
          pathinfo($tn_uri, PATHINFO_EXTENSION) .
          ';base64,' .
          base64_encode($content);

        $blazies->set('blur.data', $blur);
      }

      // Sets blur.uri.
      $blazies->set('blur.uri', $tn_uri)
        ->set('blur.url', $tn_url);
    }
  }

  /**
   * Ensures the thumbnail exists before creating a dataURI.
   */
  private static function derivative(&$blazies, $uri, $tn_uri, $style, $key = 'blur'): bool {
    if (BlazyFile::isValidUri($tn_uri)) {
      $blazies->set($key . '.uri', $tn_uri);
      if (!$blazies->get($key . '.checked')) {
        if ($style && !is_file($tn_uri)) {
          $style->createDerivative($uri, $tn_uri);
        }
        $blazies->set($key . '.checked', TRUE);
      }
      return is_file($tn_uri);
    }
    return FALSE;
  }

  /**
   * Checks for blur settings, required Image style and dimensions setup.
   *
   * Other usages: slider thumbnail/ navigation, thumbnailed pagination/ dots,
   * placeholder, thumbnailed slider arrows, zoomed/ projected image like
   * Splidebox/ PhotoSwipe, etc.
   *
   * @see self::prepare()
   */
  private static function thumbnails(array &$settings): void {
    $blazies = $settings['blazies'];
    $style   = $blazies->get('thumbnail.style');
    $width   = $height = 1;
    $uri     = $blazies->get('image.uri');
    $tn_uri  = $settings['thumbnail_uri'] ?? NULL;
    $tn_uri  = $blazies->get('thumbnail.uri') ?: $tn_uri;
    $tn_url  = '';

    // Supports unique thumbnail different from main image, such as logo for
    // thumbnail and main image for company profile.
    if ($tn_uri) {
      // $tn_url = BlazyImage::toUrl($settings, $style, $tn_uri);
      $tn_url = BlazyImage::url($tn_uri, $style);
    }

    // This one uses non-unique image, similar to the main stage image.
    if ($style) {
      $disabled = $blazies->is('external') || $blazies->is('svg');
      if (!$disabled) {
        $_tn_uri = $style->buildUri($uri);
        // $tn_url = BlazyImage::toUrl($settings, $style, $uri);
        $_tn_url = BlazyImage::url($uri, $style);

        // The latter allows keeping original for [data-b-thumb], while having
        // unique thumbnails for navigation. Not good for pagination/ dots.
        if (!$tn_url || $blazies->use('thumbnail_original')) {
          $tn_uri = $_tn_uri;
          $tn_url = $_tn_url;
        }

        $width  = $blazies->get('thumbnail.width');
        $height = $blazies->get('thumbnail.height');

        // Keep overriden/ original thumbnail data intact.
        $blazies->set('thumbnail.original.uri', $_tn_uri)
          ->set('thumbnail.original.url', $_tn_url);
      }
    }

    // With CSS background, IMG may be empty, add thumbnail to the container.
    $blazies->set('thumbnail.url', $tn_url);

    // SVG is scalable, can be used as a thumbnail as long as style is defined.
    if (!$tn_url && $blazies->is('svg') && $style) {
      $svg_url = $blazies->get('image.url');
      $blazies->set('thumbnail.url', $svg_url);
    }

    if ($tn_url) {
      self::derivative($blazies, $uri, $tn_uri, $style, 'thumbnail');
    }

    // @todo use the thumbnail size, not original ones, see: #3210759?
    $blazies->set('placeholder.width', $width)
      ->set('placeholder.height', $height);

    // Accepts configurable placeholder, alter, and fallback.
    $default = self::generate($width, $height);
    $placeholder = $blazies->ui('placeholder') ?: $default;
    $blazies->set('placeholder.url', $placeholder);

    if ($blazies->get('resimage.id')) {
      BlazyResponsiveImage::fallback($settings, $placeholder);

      // @todo decide priority whether various thumbnails or one fallback style.
      // Thumbnail gives more selective styles per field than a single fallback.
      // If thumbnail, move it to the top. This is to preserve old behaviors.
      if ($restyle = $blazies->get('resimage.fallback.style')) {
        $style = $restyle;
      }
    }

    if ($blazies->use('blur')) {
      // Creates `data:image` for blur effect if so configured and applicable.
      self::dataImage($settings, $uri, $tn_uri, $tn_url, $style);
    }
  }

}
