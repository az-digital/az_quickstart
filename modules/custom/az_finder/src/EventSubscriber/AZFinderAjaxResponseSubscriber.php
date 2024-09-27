<?php

declare(strict_types=1);

namespace Drupal\az_finder\EventSubscriber;

use Drupal\views\Ajax\ViewAjaxResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Modify AJAX responses for views using the Quickstart Exposed Filters plugin.
 */
class AZFinderAjaxResponseSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

  /**
   * Removes scrollTop AJAX commands for views with Quickstart Exposed Filters.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    // Only modify commands if this is an AJAX response for a view using
    // Quickstart Exposed Filters for the exposed form.
    if ($response instanceof ViewAjaxResponse &&
      $response->getView()->display_handler->getOption('exposed_form')['type'] === 'az_better_exposed_filters') {
      $commands = &$response->getCommands();
      foreach ($commands as $key => $value) {
        if ($value['command'] === 'scrollTop') {
          unset($commands[$key]);
          // Only one scrollTop command is expected.
          return;
        }
      }
    }
  }

}
