<?php

namespace Drupal\az_cas_guest\Authentication;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\TimeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for handling CAS guest authentication.
 */
class CasGuestAuthenticationService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The time service.
   *
   * @var \Drupal\Core\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new CasGuestAuthenticationService.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AccountInterface $current_user,
    RequestStack $request_stack,
    TimeInterface $time
  ) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->requestStack = $request_stack;
    $this->time = $time;
  }

  /**
   * Get a redirect response to the configured destination.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response.
   */
  public function getRedirectResponse() {
    $config = $this->configFactory->get('az_cas_guest.settings');
    $destination = $config->get('redirect_path') ?: '/';
    return new RedirectResponse($destination);
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