<?php

namespace Drupal\cas\Subscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alters the user password reset routes.
 */
class CasRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach (['user.pass', 'user.pass.http'] as $route_name) {
      $collection->get($route_name)->setRequirement('_cas_user_access', 'TRUE');
    }
  }

}
