<?php

namespace Drupal\az_cas\Subscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides a PreventPasswordResetSubscriber.
 */
class PreventPasswordResetSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::ALTER][] = ['alter', 0];
    return $events;
  }

  /**
   * Alter the user password reset route to prevent access.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The routing event.
   */
  public function alter(RouteBuildEvent $event) {
    if ($this->configFactory->get('az_cas.settings')->get('disable_password_recovery_link')) {
      $route_collection = $event->getRouteCollection();
      $route_collection->get('user.pass')->setRequirement('_access', 'FALSE');
    }
  }

}
