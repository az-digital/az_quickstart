<?php

declare(strict_types=1);

namespace Drupal\az_core\EventSubscriber;

use Drupal\Core\EventSubscriber\ResponseGeneratorSubscriber;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Subscriber to add distribution X-Generator header tag.
 */
class AZGeneratorSubscriber extends ResponseGeneratorSubscriber {

  /**
   * Sets replacement X-Generator header on successful responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $response = $event->getResponse();

    $response->headers->set('X-Generator', 'Arizona Quickstart (https://quickstart.arizona.edu)');
  }

}
