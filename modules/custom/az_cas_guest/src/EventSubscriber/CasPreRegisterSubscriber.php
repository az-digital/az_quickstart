<?php

namespace Drupal\az_cas_guest\EventSubscriber;

use Drupal\az_cas_guest\Service\CasGuestManager;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\TimeInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\UserAuthInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for CAS pre-register events.
 */
class CasPreRegisterSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

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
   * The CAS guest manager service.
   *
   * @var \Drupal\az_cas_guest\Service\CasGuestManager
   */
  protected $casGuestManager;

  /**
   * The user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * Constructs a new CasPreRegisterSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\az_cas_guest\Service\CasGuestManager $cas_guest_manager
   *   The CAS guest manager service.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication service.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   */
  public function __construct(
    AccountInterface $current_user,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    RequestStack $request_stack,
    TimeInterface $time,
    CasGuestManager $cas_guest_manager,
    UserAuthInterface $user_auth,
    SessionManagerInterface $session_manager
  ) {
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->loggerFactory = $logger_factory->get('az_cas_guest');
    $this->requestStack = $request_stack;
    $this->time = $time;
    $this->casGuestManager = $cas_guest_manager;
    $this->userAuth = $user_auth;
    $this->sessionManager = $session_manager;
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
    // If it does, let the normal CAS flow handle it.
    if ($this->casGuestManager->userExists($cas_username)) {
      return;
    }
    
    // If we're using a shared account.
    if ($this->configFactory->get('az_cas_guest.settings')->get('use_shared_account')) {
      // Prevent individual user registration.
      $event->preventRegistration();
      
      // Get or create the shared guest account.
      $guest_account = $this->casGuestManager->getOrCreateGuestAccount();
      
      // Log in as the shared guest account.
      $this->userAuth->authenticate($guest_account->getAccountName(), $guest_account->getPassword());
      
      // Ensure the session is started.
      if (!$this->sessionManager->isStarted()) {
        $this->sessionManager->start();
      }
      
      // Store the original CAS username in the session.
      $session = $this->requestStack->getCurrentRequest()->getSession();
      $session->set('az_cas_guest', [
        'authenticated' => TRUE,
        'cas_username' => $cas_username,
        'timestamp' => $this->time->getRequestTime(),
      ]);
      
      // Log the guest authentication.
      $this->loggerFactory->notice('CAS guest authentication for @username using shared account', [
        '@username' => $cas_username,
      ]);
    }
    // If we're just preventing user creation without a shared account.
    elseif ($this->configFactory->get('az_cas_guest.settings')->get('prevent_user_creation')) {
      // Prevent user registration.
      $event->preventRegistration();
      
      // Store the username in the session.
      $session = $this->requestStack->getCurrentRequest()->getSession();
      $session->set('az_cas_guest', [
        'authenticated' => TRUE,
        'cas_username' => $cas_username,
        'timestamp' => $this->time->getRequestTime(),
      ]);
      
      // Log the guest authentication.
      $this->loggerFactory->notice('CAS guest authentication for @username', [
        '@username' => $cas_username,
      ]);
    }
  }

}