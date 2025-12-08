<?php

namespace Drupal\az_cas\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service for managing guest authentication sessions.
 */
class GuestSessionManager {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new GuestSessionManager.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(
    StateInterface $state,
    RequestStack $request_stack,
    TimeInterface $time,
    ConfigFactoryInterface $config_factory,
  ) {
    $this->state = $state;
    $this->requestStack = $request_stack;
    $this->time = $time;
    $this->configFactory = $config_factory;
  }

  /**
   * Store guest session information.
   *
   * @param string $cas_username
   *   The CAS username.
   * @param string $session_id
   *   The session ID.
   * @param string $ip_address
   *   The IP address of the user.
   */
  public function storeGuestSession($cas_username, $session_id, $ip_address = NULL) {
    // Get session lifetime from config (default 8 hours).
    $config = $this->configFactory->get('az_cas.settings');
    $lifetime_hours = $config->get('guest_session_lifetime') ?? 8;

    // Calculate expiration time.
    $expiration = $this->time->getRequestTime() + ($lifetime_hours * 3600);

    // Store in state.
    $guest_sessions = $this->state->get('az_cas.guest_sessions', []);
    $guest_sessions[$session_id] = [
      'cas_username' => $cas_username,
      'created' => $this->time->getRequestTime(),
      'expires' => $expiration,
      'ip' => $ip_address ?: $this->requestStack->getCurrentRequest()->getClientIp(),
    ];

    $this->state->set('az_cas.guest_sessions', $guest_sessions);

    // Also store in the user's session for quick access.
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $session->set('az_cas_guest', [
      'authenticated' => TRUE,
      'cas_username' => $cas_username,
      'timestamp' => $this->time->getRequestTime(),
      'session_id' => $session_id,
    ]);
  }

  /**
   * Validate a guest session.
   *
   * @param array $session_data
   *   The session data to validate.
   *
   * @return bool
   *   TRUE if the session is valid, FALSE otherwise.
   */
  public function validateGuestSession($session_data) {
    // If no session data, not authenticated.
    if (empty($session_data) || empty($session_data['authenticated'])) {
      return FALSE;
    }

    // Get session ID.
    $session_id = $session_data['session_id'] ?? '';
    if (empty($session_id)) {
      return FALSE;
    }

    // Check server-side record.
    $guest_sessions = $this->state->get('az_cas.guest_sessions', []);
    if (!isset($guest_sessions[$session_id])) {
      return FALSE;
    }

    // Check expiration.
    $server_record = $guest_sessions[$session_id];
    $current_time = $this->time->getRequestTime();
    if ($server_record['expires'] < $current_time) {
      // Session expired, remove it.
      unset($guest_sessions[$session_id]);
      $this->state->set('az_cas.guest_sessions', $guest_sessions);
      return FALSE;
    }

    // Validate username matches.
    if ($server_record['cas_username'] !== $session_data['cas_username']) {
      return FALSE;
    }

    // Check IP address if configured to do so.
    $config = $this->configFactory->get('az_cas.settings');
    if ($config->get('guest_ip_validation') &&
        $server_record['ip'] !== $this->requestStack->getCurrentRequest()->getClientIp()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Clean up expired guest sessions.
   */
  public function cleanupExpiredSessions() {
    $guest_sessions = $this->state->get('az_cas.guest_sessions', []);
    $current_time = $this->time->getRequestTime();
    $changed = FALSE;

    foreach ($guest_sessions as $session_id => $data) {
      if ($data['expires'] < $current_time) {
        unset($guest_sessions[$session_id]);
        $changed = TRUE;
      }
    }

    if ($changed) {
      $this->state->set('az_cas.guest_sessions', $guest_sessions);
    }
  }

}
