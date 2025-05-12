<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\az_cas\Exception\GuestRedirectException;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\cas\Service\CasUserManager;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for CAS pre-register events.
 */
class CasPreRegisterSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The CAS user manager service.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new CasPreRegisterSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The CAS user manager service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    CasUserManager $cas_user_manager,
    TimeInterface $time,
  ) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('az_cas');
    $this->requestStack = $request_stack;
    $this->casUserManager = $cas_user_manager;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasHelper::EVENT_PRE_REGISTER][] = ['onCasPreRegister', 100];
    return $events;
  }

  /**
   * Respond to CAS pre-register event.
   *
   * @param \Drupal\cas\Event\CasPreRegisterEvent $event
   *   The CAS pre-register event.
   */
  public function onCasPreRegister(CasPreRegisterEvent $event) {
    // Get the CAS username.
    $cas_username = $event->getCasPropertyBag()->getUsername();

    // Check if a user account already exists for this CAS username.
    $uid = $this->casUserManager->getUidForCasUsername($cas_username);
    if ($uid) {
      return;
    }

    // If guest mode is enabled.
    if ($this->configFactory->get('az_cas.settings')->get('guest_mode')) {
      // Prevent user registration without showing an error message.
      $event->cancelAutomaticRegistration();

      // Store the username in the session.
      $session = $this->requestStack->getCurrentRequest()->getSession();
      $session->set('az_cas_guest', [
        'authenticated' => TRUE,
        'cas_username' => $cas_username,
        'timestamp' => $this->time->getRequestTime(),
      ]);

      // Log the guest authentication.
      $this->logger->notice('Quickstart CAS guest authentication for @username', [
        '@username' => $cas_username,
      ]);

      // Get the destination from the request query.
      $request = $this->requestStack->getCurrentRequest();
      $destination = $request->query->get('destination');

      // If we have a destination, throw a redirect exception.
      if ($destination) {
        throw new GuestRedirectException($destination);
      }
      else {
        // Redirect to the front page as a fallback.
        throw new GuestRedirectException('/');
      }
    }
  }

}
