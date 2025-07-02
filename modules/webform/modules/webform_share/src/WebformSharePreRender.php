<?php

namespace Drupal\webform_share;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Implements trusted prerender callbacks for the Webform share module.
 *
 * @internal
 */
class WebformSharePreRender implements TrustedCallbackInterface {

  /**
   * Prerender callback for page.
   */
  public static function page($element) {
    if (!WebformShareHelper::isPage()) {
      return $element;
    }

    // Remove all theme wrappers from the page template.
    $element['#theme_wrappers'] = [];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['page'];
  }

}
