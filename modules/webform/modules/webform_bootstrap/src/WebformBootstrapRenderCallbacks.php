<?php

namespace Drupal\webform_bootstrap;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderCallbackInterface;

/**
 * Render callbacks for the webform bootstrap module.
 */
class WebformBootstrapRenderCallbacks implements RenderCallbackInterface {

  /**
   * Render callback for the likerts element.
   *
   * @param array $element
   *   The render array.
   *
   * @return array
   *   The altered render array.
   */
  public static function webformLikertPreRender(array $element) {
    foreach (Element::children($element) as $element_key) {
      // Likerts allow description display to be configured, so disable
      // smart description.
      if (!empty($element[$element_key]['#description'])) {
        $element[$element_key]['#smart_description'] = FALSE;
      }
      $element[$element_key] = static::webformLikertPreRender($element[$element_key]);
    }
    return $element;
  }

}
