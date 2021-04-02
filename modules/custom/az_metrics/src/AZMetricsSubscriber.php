<?php

namespace Drupal\az_metrics\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AZMetricsSubscriber implements EventSubscriberInterface {

  public function logDomain(GetResponseEvent $event) {
    echo $event->getRequest()->getBaseUrl();
    // if ($event->getRequest()->query->get('redirect-me')) {
    //   $event->setResponse(new RedirectResponse('http://example.com/'));
    // }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('logDomain');
    return $events;
  }
}
