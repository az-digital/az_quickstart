<?php

namespace Drupal\az_gdpr_consent\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Adds Vary header for Pantheon CDN caching by GDPR status.
 */
class GdprVaryHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run after other response event subscribers.
    $events[KernelEvents::RESPONSE][] = ['onResponse', -10];
    return $events;
  }

  /**
   * Adds the Vary: X-Geo-Country-Code header to responses.
   *
   * This tells Pantheon's Global CDN to cache pages separately based on
   * the visitor's country code, ensuring GDPR and non-GDPR visitors
   * receive appropriate cached versions.
   */
  public function onResponse(ResponseEvent $event) {
    // Only modify main requests, not subrequests.
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();

    // Get existing Vary headers.
    $vary_headers = $response->getVary();

    // Add X-Geo-Country-Code if not already present.
    if (!in_array('X-Geo-Country-Code', $vary_headers)) {
      $vary_headers[] = 'X-Geo-Country-Code';
      $response->setVary($vary_headers);
    }
  }

}
