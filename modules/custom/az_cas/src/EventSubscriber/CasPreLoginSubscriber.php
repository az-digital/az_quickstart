<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\cas\Event\CasPreLoginEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for CAS pre-login events.
 */
class CasPreLoginSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CasPreLoginSubscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RequestStack $request_stack,
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasPreLoginEvent::class][] = ['onCasPreLogin', 100];
    return $events;
  }

  /**
   * Respond to CAS pre-login event.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The CAS pre-login event.
   */
  public function onCasPreLogin(CasPreLoginEvent $event) {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $guest_data = $session->get('az_cas_guest');

    // If this is a guest session, cancel the login process.
    if (!empty($guest_data['authenticated'])) {
      // Cancel the login process without showing an error message.
      $event->cancelLogin();
    }
  }

}
