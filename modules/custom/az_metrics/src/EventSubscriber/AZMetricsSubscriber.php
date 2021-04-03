<?php

namespace Drupal\az_metrics\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for logging domains.
 */
class AZMetricsSubscriber implements EventSubscriberInterface {

  /**
   * The method to store the incoming domain in the database.
   */
  protected function logDomain(GetResponseEvent $event) {
    $httpHost = $event->getRequest()->getHttpHost();
    $connection = \Drupal::service('database');
    $connection->merge('az_metrics_domains')
      ->key('domain', $httpHost)
      ->fields([
        'domain' => $httpHost,
        'last_seen' => \Drupal::time()->getRequestTime(),
      ])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['logDomain'];
    return $events;
  }

}
