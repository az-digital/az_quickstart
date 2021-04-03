<?php

namespace Drupal\az_metrics\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AZMetricsSubscriber implements EventSubscriberInterface {

  public function logDomain(GetResponseEvent $event) {
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
    $events[KernelEvents::REQUEST][] = array('logDomain');
    return $events;
  }
}
