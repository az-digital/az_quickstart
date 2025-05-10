<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\az_cas\Authentication\CasGuestAuthenticationService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for kernel request events to handle CAS forced login.
 */
class KernelRequestSubscriber implements EventSubscriberInterface {

  /**
   * The CAS guest authentication service.
   *
   * @var \Drupal\az_cas\Authentication\CasGuestAuthenticationService
   */
  protected $casGuestAuthentication;

  /**
   * Constructs a new KernelRequestSubscriber.
   *
   * @param \Drupal\az_cas\Authentication\CasGuestAuthenticationService $cas_guest_authentication
   *   The CAS guest authentication service.
   */
  public function __construct(CasGuestAuthenticationService $cas_guest_authentication) {
    $this->casGuestAuthentication = $cas_guest_authentication;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before the CAS module's forced login subscriber (priority 31).
    $events[KernelEvents::REQUEST][] = ['checkCasGuestAuthentication', 32];
    return $events;
  }

  /**
   * Check if the user is authenticated as a CAS guest.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function checkCasGuestAuthentication(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    // If the user is authenticated as a CAS guest, set a flag in the request.
    if ($this->casGuestAuthentication->isGuestSession()) {
      $event->getRequest()->attributes->set('_cas_guest_authenticated', TRUE);
    }
  }

}