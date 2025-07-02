<?php

declare(strict_types = 1);

namespace Drupal\cas\Subscriber;

use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to KernelEvents::RESPONSE events.
 */
class CasLoginRedirectSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::RESPONSE => [
        // Act before RedirectResponseSubscriber::checkRedirectUrl() which has a
        // lower priority (0).
        // @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
        ['onRedirectResponse', 5],
      ],
    ];
  }

  /**
   * Removes the 'destination' from 'cas.login' and 'cas.legacy_login' requests.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   */
  public function onRedirectResponse(ResponseEvent $event): void {
    $route_match = RouteMatch::createFromRequest($event->getRequest());
    if (in_array($route_match->getRouteName(), ['cas.login', 'cas.legacy_login'], TRUE)) {
      // Remove the "destination" parameter from the request if present. Without
      // doing so, Drupal will redirect directly to this destination instead of
      // to the CAS server. The destination param is not lost, it has been saved
      // in $cas_redirect_data, and it becomes part of the CAS service URL that
      // we redirect to.
      // @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
      // @see \Drupal\cas\Controller\ForceLoginController::forceLogin()
      if ($event->getRequest()->query->has('destination')) {
        $event->getRequest()->query->remove('destination');
      }
    }
  }

}
