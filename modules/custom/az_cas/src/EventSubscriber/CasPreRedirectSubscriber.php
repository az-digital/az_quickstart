<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\cas\Event\CasPreRedirectEvent;
use Drupal\cas\Service\CasHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for CAS pre-redirect events.
 */
class CasPreRedirectSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CasPreRedirectSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasHelper::EVENT_PRE_REDIRECT][] = ['onCasPreRedirect', 100];
    return $events;
  }

  /**
   * Respond to CAS pre-redirect event.
   *
   * @param \Drupal\cas\Event\CasPreRedirectEvent $event
   *   The CAS pre-redirect event.
   */
  public function onCasPreRedirect(CasPreRedirectEvent $event) {
    $request = $this->requestStack->getCurrentRequest();

    // If the user is already authenticated as a CAS guest,
    // prevent the redirect to the CAS server.
    if ($request->attributes->get('_cas_guest_authenticated')) {
      $event->preventRedirection();
    }
  }

}
