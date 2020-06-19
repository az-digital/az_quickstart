<?php

namespace Drupal\az_core\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Change controller for diff routes to Quickstart custom version.
    if ($route = $collection->get('config_distro.diff')) {
      $route->setDefaults([
        '_controller' => '\Drupal\az_core\Controller\QuickstartDistroController::diff',
        'target_name' => NULL,
      ]);
    }
    if ($route = $collection->get('config_distro.diff_collection')) {
      $route->setDefaults([
        '_controller' => '\Drupal\az_core\Controller\QuickstartDistroController::diff',
        'target_name' => NULL,
      ]);
    }
  }

}
