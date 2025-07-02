<?php

namespace Drupal\devel_test\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\State\State;
use Symfony\Component\Routing\RouteCollection;

/**
 * Router subscriber class for testing purpose.
 */
class TestRouteSubscriber extends RouteSubscriberBase {

  /**
   * The state store.
   */
  protected State $state;

  /**
   * Constructor method.
   *
   * @param \Drupal\Core\State\State $state
   *   The object State.
   */
  public function __construct(State $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $this->state->set('devel_test_route_rebuild', 'Router rebuild fired');
  }

}
