<?php

namespace Drupal\webform_share;

use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Webform share helper class.
 */
class WebformShareHelper {

  /**
   * Determine if the current page is a webform share page.
   *
   * @return bool
   *   TRUE if the current page is a webform share page.
   */
  public static function isPage(RouteMatchInterface $route_match = NULL) {
    $route_match = $route_match ?: \Drupal::routeMatch();
    $route_name = $route_match->getRouteName();
    return ($route_name && strpos($route_name, 'entity.webform.share_page') === 0);
  }

}
