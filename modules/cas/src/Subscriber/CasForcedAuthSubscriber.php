<?php

namespace Drupal\cas\Subscriber;

use Drupal\cas\CasRedirectData;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasRedirector;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for implementing CAS forced authentication.
 */
class CasForcedAuthSubscriber extends HttpExceptionSubscriberBase {

  /**
   * Route matcher object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatcher;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * CAS helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * CasRedirector.
   *
   * @var \Drupal\cas\Service\CasRedirector
   */
  protected $casRedirector;

  /**
   * Is forced login configuration setting enabled.
   *
   * @var bool
   */
  protected $forcedLoginEnabled = FALSE;

  /**
   * Paths to check for forced login.
   *
   * @var array
   */
  protected $forcedLoginPaths = [];

  /**
   * Constructs a new CasSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_matcher
   *   The route matcher.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition manager.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS Helper service.
   * @param \Drupal\cas\Service\CasRedirector $cas_redirector
   *   The CAS Redirector Service.
   */
  public function __construct(RouteMatchInterface $route_matcher, ConfigFactoryInterface $config_factory, AccountInterface $current_user, ConditionManager $condition_manager, CasHelper $cas_helper, CasRedirector $cas_redirector) {
    $this->routeMatcher = $route_matcher;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->conditionManager = $condition_manager;
    $this->casHelper = $cas_helper;
    $this->casRedirector = $cas_redirector;
    $settings = $this->configFactory->get('cas.settings');
    $this->forcedLoginPaths = $settings->get('forced_login.paths');
    $this->forcedLoginEnabled = $settings->get('forced_login.enabled');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before DynamicPageCacheSubscriber (27) and
    // CasGatewayAuthSubscriber (28)
    // but after important services like RouterListener (32) and
    // MaintenanceModeSubscriber (30)
    $events[KernelEvents::REQUEST][] = ['onRequest', 29];

    $events[KernelEvents::EXCEPTION][] = ['onException', 0];
    return $events;
  }

  /**
   * Respond to kernel request set forced auth redirect response.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event.
   */
  public function onRequest(RequestEvent $event) {
    // Don't do anything if this is a sub request and not a master request.
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Some routes we don't want to run on.
    $current_route = $this->routeMatcher->getRouteName();
    if (in_array($current_route, CasHelper::IGNOREABLE_AUTO_LOGIN_ROUTES)) {
      return;
    }

    // Only care about anonymous users.
    if ($this->currentUser->isAuthenticated()) {
      return;
    }

    if (!$this->forcedLoginEnabled) {
      return;
    }

    // Check if user provided specific paths to force/not force a login.
    $condition = $this->conditionManager->createInstance('request_path');
    $condition->setConfiguration($this->forcedLoginPaths);
    if (!$this->conditionManager->execute($condition)) {
      return;
    }

    $this->casHelper->log(LogLevel::DEBUG, 'Initializing forced login auth from CasSubscriber.');

    // Start constructing the URL redirect to CAS for forced auth.
    // Add the current path to the service URL as the 'destination' param,
    // so that when the ServiceController eventually processess the login,
    // it knows to return the user back here.
    $request = $event->getRequest();
    $currentPath = str_replace($request->getSchemeAndHttpHost(), '', $request->getUri());
    $redirectData = new CasRedirectData(['destination' => $currentPath]);

    $response = $this->casRedirector->buildRedirectResponse($redirectData);
    if ($response) {
      $event->setResponse($response);
      // If there's a 'destination' parameter set on the current request,
      // remove it, otherwise Drupal's RedirectResponseSubscriber will send
      // users to that location instead of to our CAS server.
      $request->query->remove('destination');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['html'];
  }

  /**
   * Handle 403 errors.
   *
   * Other request subscribers with a higher priority may intercept the request
   * and return a 403 before our request subscriber can handle it. In those
   * instances we handle the forced login redirect if applicable here instead,
   * using an exception subscriber.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function on403(ExceptionEvent $event) {
    $this->onRequest($event);
  }

}
