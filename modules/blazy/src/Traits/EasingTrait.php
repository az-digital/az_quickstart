<?php

namespace Drupal\blazy\Traits;

/**
 * A Trait for easings, common for Splide, Slick, etc.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
trait EasingTrait {

  /**
   * The JS easing options.
   *
   * @var array
   */
  protected $jsEasingOptions;

  /**
   * List of all easing methods available from jQuery Easing v1.3.
   *
   * @return array
   *   An array of available jQuery Easing options as fallback for browsers that
   *   don't support pure CSS easing.
   */
  protected function getJsEasingOptions() {
    if (!isset($this->jsEasingOptions)) {
      $this->jsEasingOptions = [
        'linear'           => 'Linear',
        'swing'            => 'Swing',
        'easeInQuad'       => 'easeInQuad',
        'easeOutQuad'      => 'easeOutQuad',
        'easeInOutQuad'    => 'easeInOutQuad',
        'easeInCubic'      => 'easeInCubic',
        'easeOutCubic'     => 'easeOutCubic',
        'easeInOutCubic'   => 'easeInOutCubic',
        'easeInQuart'      => 'easeInQuart',
        'easeOutQuart'     => 'easeOutQuart',
        'easeInOutQuart'   => 'easeInOutQuart',
        'easeInQuint'      => 'easeInQuint',
        'easeOutQuint'     => 'easeOutQuint',
        'easeInOutQuint'   => 'easeInOutQuint',
        'easeInSine'       => 'easeInSine',
        'easeOutSine'      => 'easeOutSine',
        'easeInOutSine'    => 'easeInOutSine',
        'easeInExpo'       => 'easeInExpo',
        'easeOutExpo'      => 'easeOutExpo',
        'easeInOutExpo'    => 'easeInOutExpo',
        'easeInCirc'       => 'easeInCirc',
        'easeOutCirc'      => 'easeOutCirc',
        'easeInOutCirc'    => 'easeInOutCirc',
        'easeInElastic'    => 'easeInElastic',
        'easeOutElastic'   => 'easeOutElastic',
        'easeInOutElastic' => 'easeInOutElastic',
        'easeInBack'       => 'easeInBack',
        'easeOutBack'      => 'easeOutBack',
        'easeInOutBack'    => 'easeInOutBack',
        'easeInBounce'     => 'easeInBounce',
        'easeOutBounce'    => 'easeOutBounce',
        'easeInOutBounce'  => 'easeInOutBounce',
      ];
    }
    return $this->jsEasingOptions;
  }

  /**
   * List of available CSS easing methods.
   *
   * @param bool $map
   *   Flag to output the array as is for further processing if TRUE.
   *
   * @return array
   *   An array of CSS easings for select options, or all for the mappings.
   *
   * @see https://github.com/kenwheeler/slick/issues/118
   * @see https://matthewlein.com/ceaser/
   * @see https://www.w3.org/TR/css3-transitions/
   */
  protected function getCssEasingOptions($map = FALSE) {
    $css_easings = [];
    $available_easings = [

      // Defaults/ Native.
      'ease'           => 'ease|ease',
      'linear'         => 'linear|linear',
      'ease-in'        => 'ease-in|ease-in',
      'ease-out'       => 'ease-out|ease-out',
      'swing'          => 'swing|ease-in-out',

      // Penner Equations (approximated).
      'easeInQuad'     => 'easeInQuad|cubic-bezier(0.550, 0.085, 0.680, 0.530)',
      'easeInCubic'    => 'easeInCubic|cubic-bezier(0.550, 0.055, 0.675, 0.190)',
      'easeInQuart'    => 'easeInQuart|cubic-bezier(0.895, 0.030, 0.685, 0.220)',
      'easeInQuint'    => 'easeInQuint|cubic-bezier(0.755, 0.050, 0.855, 0.060)',
      'easeInSine'     => 'easeInSine|cubic-bezier(0.470, 0.000, 0.745, 0.715)',
      'easeInExpo'     => 'easeInExpo|cubic-bezier(0.950, 0.050, 0.795, 0.035)',
      'easeInCirc'     => 'easeInCirc|cubic-bezier(0.600, 0.040, 0.980, 0.335)',
      'easeInBack'     => 'easeInBack|cubic-bezier(0.600, -0.280, 0.735, 0.045)',
      'easeOutQuad'    => 'easeOutQuad|cubic-bezier(0.250, 0.460, 0.450, 0.940)',
      'easeOutCubic'   => 'easeOutCubic|cubic-bezier(0.215, 0.610, 0.355, 1.000)',
      'easeOutQuart'   => 'easeOutQuart|cubic-bezier(0.165, 0.840, 0.440, 1.000)',
      'easeOutQuint'   => 'easeOutQuint|cubic-bezier(0.230, 1.000, 0.320, 1.000)',
      'easeOutSine'    => 'easeOutSine|cubic-bezier(0.390, 0.575, 0.565, 1.000)',
      'easeOutExpo'    => 'easeOutExpo|cubic-bezier(0.190, 1.000, 0.220, 1.000)',
      'easeOutCirc'    => 'easeOutCirc|cubic-bezier(0.075, 0.820, 0.165, 1.000)',
      'easeOutBack'    => 'easeOutBack|cubic-bezier(0.175, 0.885, 0.320, 1.275)',
      'easeInOutQuad'  => 'easeInOutQuad|cubic-bezier(0.455, 0.030, 0.515, 0.955)',
      'easeInOutCubic' => 'easeInOutCubic|cubic-bezier(0.645, 0.045, 0.355, 1.000)',
      'easeInOutQuart' => 'easeInOutQuart|cubic-bezier(0.770, 0.000, 0.175, 1.000)',
      'easeInOutQuint' => 'easeInOutQuint|cubic-bezier(0.860, 0.000, 0.070, 1.000)',
      'easeInOutSine'  => 'easeInOutSine|cubic-bezier(0.445, 0.050, 0.550, 0.950)',
      'easeInOutExpo'  => 'easeInOutExpo|cubic-bezier(1.000, 0.000, 0.000, 1.000)',
      'easeInOutCirc'  => 'easeInOutCirc|cubic-bezier(0.785, 0.135, 0.150, 0.860)',
      'easeInOutBack'  => 'easeInOutBack|cubic-bezier(0.680, -0.550, 0.265, 1.550)',
    ];

    foreach ($available_easings as $key => $easing) {
      [$readable_easing, $css_easing] = array_pad(array_map('trim', explode("|", $easing, 2)), 2, NULL);
      $css_easings[$key] = $map ? $easing : $readable_easing;
      unset($css_easing);
    }
    return $css_easings;
  }

  /**
   * Maps existing jQuery easing value to equivalent CSS easing methods.
   *
   * @param string $easing
   *   The name of the human readable easing.
   *
   * @return string
   *   A string of unfriendly bezier equivalent, or NULL.
   */
  protected function getBezier($easing = NULL) {
    $css_easing = '';
    if ($easing) {
      $easings = $this->getCssEasingOptions(TRUE);
      [$readable_easing, $bezier] = array_pad(array_map('trim', explode("|", $easings[$easing], 2)), 2, NULL);
      $css_easing = $bezier;
      unset($readable_easing);
    }
    return $css_easing;
  }

}
