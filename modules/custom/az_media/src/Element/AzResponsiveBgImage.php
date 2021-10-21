<?php

namespace Drupal\az_media\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a style element with responsive background image css.
 *
 * @RenderElement("az_responsive_background_image")
 */
class AzResponsiveBgImage extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'az_responsive_background_image',
    ];
  }

}
