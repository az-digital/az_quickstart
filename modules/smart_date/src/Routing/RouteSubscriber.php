<?php

namespace Drupal\smart_date\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Conditionally provide routing information.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Alter existing routes as needed.
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('fullcalendar_view.update_event')) {
      $route->setDefault('_controller', "\Drupal\smart_date\Controller\FullCalendarController::updateEvent");
    }
  }

}
