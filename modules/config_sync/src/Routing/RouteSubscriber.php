<?php

namespace Drupal\config_sync\Routing;

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
    // Use our form for distribution imports.
    if ($route = $collection->get('config_distro.import')) {
      $route->setDefault('_form', '\Drupal\config_sync\Form\ConfigSyncImportForm');
    }
  }

}
