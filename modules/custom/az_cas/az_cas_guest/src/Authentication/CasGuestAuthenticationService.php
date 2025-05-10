<?php

namespace Drupal\az_cas_guest\Authentication;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for handling CAS guest authentication.
 */
class CasGuestAuthenticationService {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CasGuestAuthenticationService.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    RequestStack $request_stack
  ) {
    $this->requestStack = $request_stack;
  }

  /**
   * Check if the current session is a guest CAS session.
   *
   * @return bool
   *   TRUE if the current session is a guest CAS session.
   */
  public function isGuestSession() {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $guest_data = $session->get('az_cas_guest');
    return !empty($guest_data['authenticated']);
  }

  /**
   * Get the CAS username for the current guest session.
   *
   * @return string|null
   *   The CAS username, or NULL if not a guest session.
   */
  public function getGuestUsername() {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $guest_data = $session->get('az_cas_guest');
    return $guest_data['cas_username'] ?? NULL;
  }

}