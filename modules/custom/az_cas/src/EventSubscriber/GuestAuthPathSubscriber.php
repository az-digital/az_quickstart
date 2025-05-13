<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\az_cas\Service\GuestSessionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to handle guest authentication paths.
 */
class GuestAuthPathSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The guest session manager.
   *
   * @var \Drupal\az_cas\Service\GuestSessionManager
   */
  protected $guestSessionManager;

  /**
   * Constructs a new GuestAuthPathSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\az_cas\Service\GuestSessionManager $guest_session_manager
   *   The guest session manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    PathMatcherInterface $path_matcher,
    AccountProxyInterface $current_user,
    LoggerChannelFactoryInterface $logger_factory,
    GuestSessionManager $guest_session_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->pathMatcher = $path_matcher;
    $this->currentUser = $current_user;
    $this->logger = $logger_factory->get('az_cas');
    $this->guestSessionManager = $guest_session_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Run before the CAS module's forced login subscriber (priority 29).
    // We use a higher priority to ensure we run first.
    $events[KernelEvents::REQUEST][] = ['handleGuestAuthPaths', 35];
    return $events;
  }

  /**
   * Handle guest authentication paths.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function handleGuestAuthPaths(RequestEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $az_cas_settings = $this->configFactory->get('az_cas.settings');

    // Only proceed if guest mode is enabled.
    if (!$az_cas_settings->get('guest_mode')) {
      return;
    }

    // Check if this is a path that allows authenticated NetID guest access.
    $current_path = $request->getPathInfo();
    $guest_auth_paths = $az_cas_settings->get('guest_auth_paths') ?: [];

    // Check if the current path matches any of the guest authentication paths.
    $path_matches = FALSE;
    foreach ($guest_auth_paths as $path) {
      if ($this->pathMatcher->matchPath($current_path, $path)) {
        $path_matches = TRUE;
        break;
      }
    }

    // Check if the user is authenticated as a guest.
    $session = $request->getSession();
    $guest_data = $session->get('az_cas_guest');

    // Validate the guest session using the session manager.
    $is_guest_authenticated = $this->guestSessionManager->validateGuestSession($guest_data);

    // If this is a guest authentication path and the user is authenticated as a
    // guest, allow access.
    if ($path_matches && $is_guest_authenticated) {
      $request->attributes->set('_cas_guest_authenticated', TRUE);
      return;
    }

    // If this is a guest authentication path, the user is not authenticated as
    // a guest, and the user is anonymous, redirect to CAS login.
    if ($path_matches && !$is_guest_authenticated && $this->currentUser->isAnonymous()) {
      // Get the CAS login path from configuration.
      $cas_config = $this->configFactory->get('cas.settings');
      $cas_login_path = '/' . ltrim($cas_config->get('login_link_path') ?: 'cas', '/');

      // Create a redirect response to the CAS login path.
      $destination = $request->getPathInfo();
      $query = $request->getQueryString();
      if ($query) {
        $destination .= '?' . $query;
      }

      // Use the standard destination parameter directly in the URL.
      $url = $cas_login_path . '?destination=' . urlencode($destination);
      $response = new RedirectResponse($url);
      $event->setResponse($response);
      $event->stopPropagation();
    }
  }

}
