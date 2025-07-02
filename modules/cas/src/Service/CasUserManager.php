<?php

namespace Drupal\cas\Service;

use Drupal\cas\CasPropertyBag;
use Drupal\cas\Event\CasPostLoginEvent;
use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Event\CasPreRegisterEvent;
use Drupal\cas\Exception\CasLoginException;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\user\UserInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the 'cas.user_manager' service default implementation.
 */
class CasUserManager {

  /**
   * Email address for new users is combo of username + custom hostname.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_STANDARD = 0;

  /**
   * Email address for new users is derived from a CAS attirbute.
   *
   * @var int
   */
  const EMAIL_ASSIGNMENT_ATTRIBUTE = 1;

  /**
   * Used to include the externalauth service from the external_auth module.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * An authmap service object.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * Stores settings object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $settings;

  /**
   * Used to get session data.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * Used when storing CAS login data.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The CAS Helper.
   *
   * @var \Drupal\cas\Service\CasHelper
   */
  protected $casHelper;

  /**
   * CAS proxy helper.
   *
   * @var \Drupal\cas\Service\CasProxyHelper
   */
  protected $casProxyHelper;

  /**
   * Used to dispatch CAS login events.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The name of the external auth provider we use.
   *
   * @var string
   */
  protected $provider = 'cas';

  /**
   * The password generator service.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected PasswordGeneratorInterface $passwordGenerator;

  /**
   * Whether admin approval is required on new user accounts registration.
   *
   * @var bool
   */
  protected $adminApprovalNeeded;

  /**
   * CasUserManager constructor.
   *
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth interface.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap interface.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $settings
   *   The settings.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Drupal\Core\Database\Connection $database_connection
   *   The database connection.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\cas\Service\CasHelper $cas_helper
   *   The CAS helper.
   * @param \Drupal\cas\Service\CasProxyHelper $cas_proxy_helper
   *   The CAS Proxy helper.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password generator service.
   */
  public function __construct(ExternalAuthInterface $external_auth, AuthmapInterface $authmap, ConfigFactoryInterface $settings, SessionInterface $session, Connection $database_connection, EventDispatcherInterface $event_dispatcher, CasHelper $cas_helper, CasProxyHelper $cas_proxy_helper, PasswordGeneratorInterface $password_generator) {
    $this->externalAuth = $external_auth;
    $this->authmap = $authmap;
    $this->settings = $settings;
    $this->session = $session;
    $this->connection = $database_connection;
    $this->eventDispatcher = $event_dispatcher;
    $this->casHelper = $cas_helper;
    $this->casProxyHelper = $cas_proxy_helper;
    $this->passwordGenerator = $password_generator;
  }

  /**
   * Register a local Drupal user given a CAS username.
   *
   * @param string $authname
   *   The CAS username.
   * @param string $local_username
   *   The local Drupal username to be created.
   * @param array $property_values
   *   (optional) Property values to assign to the user on registration.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity of the newly registered user.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   When the user account could not be registered.
   */
  public function register($authname, $local_username, array $property_values = []) {
    $property_values['name'] = $local_username;
    $property_values['pass'] = $this->randomPassword();
    // Respect a previous status set by any of the upstream subscribers.
    $property_values['status'] ??= (int) !$this->isAdminApprovalNeeded();

    try {
      $user = $this->externalAuth->register($authname, $this->provider, $property_values);
    }
    catch (ExternalAuthRegisterException $e) {
      throw new CasLoginException($e->getMessage(), CasLoginException::USERNAME_ALREADY_EXISTS);
    }
    return $user;
  }

  /**
   * Attempts to log the user in to the Drupal site.
   *
   * @param \Drupal\cas\CasPropertyBag $property_bag
   *   CasPropertyBag containing username and attributes from CAS.
   * @param string $ticket
   *   The service ticket.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown if there was a problem logging in the user.
   */
  public function login(CasPropertyBag $property_bag, $ticket) {
    $account = $this->externalAuth->load($property_bag->getUsername(), $this->provider);
    if ($account === FALSE) {
      // Check if we should create the user or not.
      $config = $this->settings->get('cas.settings');
      if ($config->get('user_accounts.auto_register') === TRUE) {
        $this->casHelper->log(
          LogLevel::DEBUG,
          'Existing account not found for user, attempting to auto-register.'
        );

        // Dispatch an event that allows modules to deny automatic registration
        // for this user account or to set properties for the user that will
        // be created.
        $cas_pre_register_event = new CasPreRegisterEvent($property_bag);
        $cas_pre_register_event->setPropertyValue('mail', $this->getEmailForNewAccount($property_bag));
        $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_REGISTER.');
        $this->eventDispatcher->dispatch($cas_pre_register_event, CasHelper::EVENT_PRE_REGISTER);
        if ($cas_pre_register_event->getAllowAutomaticRegistration()) {
          $account = $this->register($property_bag->getUsername(), $cas_pre_register_event->getDrupalUsername(), $cas_pre_register_event->getPropertyValues());
          if ($this->isAdminApprovalNeeded() && $account->isBlocked()) {
            // Cannot log in until the admins are not approving the new account.
            // Note that CAS module provides, by default, the normal Drupal
            // behavior by showing a status message and, if configured, sending
            // email notifications to user and admins. This is achieved by
            // listening to ExternalAuthEvents::REGISTER event. Third-party may
            // override this behavior by providing a subscriber with a higher
            // priority, implementing their logic and stopping the event
            // propagation.
            // @see \Drupal\cas\Subscriber\CasAdminApprovalRegistrationSubscriber
            $this->casHelper->log(LogLevel::DEBUG, 'Login denied as new account needs admin approval.');
            throw new CasLoginException("Cannot login, admin approval is required for new accounts", CasLoginException::ADMIN_APPROVAL_REQUIRED);
          }
        }
        else {
          $reason = $cas_pre_register_event->getCancelRegistrationReason();
          throw (new CasLoginException(
            sprintf("Registration of user '%s' denied by an event listener.", $property_bag->getUsername()),
            CasLoginException::SUBSCRIBER_DENIED_REG
          ))->setSubscriberCancelReason($reason);
        }
      }
      else {
        throw new CasLoginException("Cannot login, local Drupal user account does not exist.", CasLoginException::NO_LOCAL_ACCOUNT);
      }
    }

    // Check if the retrieved user is blocked before moving forward.
    if ($account->isBlocked()) {
      throw new CasLoginException(sprintf('The username %s has not been activated or is blocked.', $account->getAccountName()), CasLoginException::ACCOUNT_BLOCKED);
    }

    // Dispatch an event that allows modules to prevent this user from logging
    // in and/or alter the user entity before we save it.
    $pre_login_event = new CasPreLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_PRE_LOGIN.');
    $this->eventDispatcher->dispatch($pre_login_event, CasHelper::EVENT_PRE_LOGIN);

    // Save user entity since event listeners may have altered it.
    // @todo Don't take it for granted. Find if the account was really altered.
    // @todo Should this be swapped with the following if(...) block? Why
    //   altering the account if the login has been denied?
    $account->save();

    if (!$pre_login_event->getAllowLogin()) {
      $reason = $pre_login_event->getCancelLoginReason();
      throw (new CasLoginException('Cannot login, an event listener denied access.', CasLoginException::SUBSCRIBER_DENIED_LOGIN))
        ->setSubscriberCancelReason($reason);
    }

    $this->externalAuth->userLoginFinalize($account, $property_bag->getUsername(), $this->provider);
    $this->storeLoginSessionData($ticket);
    $this->session->set('is_cas_user', TRUE);
    $this->session->set('cas_username', $property_bag->getOriginalUsername());

    $postLoginEvent = new CasPostLoginEvent($account, $property_bag);
    $this->casHelper->log(LogLevel::DEBUG, 'Dispatching EVENT_POST_LOGIN.');
    $this->eventDispatcher->dispatch($postLoginEvent, CasHelper::EVENT_POST_LOGIN);

    if ($this->settings->get('proxy.initialize') && $property_bag->getPgt()) {
      $this->casHelper->log(LogLevel::DEBUG, "Storing PGT information for this session.");
      $this->casProxyHelper->storePgtSession($property_bag->getPgt());
    }
  }

  /**
   * Store the Session ID and ticket for single-log-out purposes.
   *
   * @param string $ticket
   *   The CAS service ticket to be used as the lookup key.
   */
  protected function storeLoginSessionData($ticket) {
    if ($this->settings->get('cas.settings')->get('logout.enable_single_logout') === TRUE) {
      // TODO: We should not access the session ID here. We at least need to
      // first persist the session so a proper ID is generated first.
      // See https://www.drupal.org/project/cas/issues/3190842.
      $session_id = $this->session->getId();
      $this->connection->upsert('cas_login_data')
        ->fields(
          ['sid', 'plainsid', 'ticket', 'created'],
          [Crypt::hashBase64($session_id), $session_id, $ticket, time()]
        )
        ->key('sid')
        ->execute();
    }
  }

  /**
   * Return CAS username for account, or FALSE if it doesn't have one.
   *
   * @param int $uid
   *   The user ID.
   *
   * @return bool|string
   *   The CAS username if it exists, or FALSE otherwise.
   */
  public function getCasUsernameForAccount($uid) {
    return $this->authmap->get($uid, $this->provider);
  }

  /**
   * Return uid of account associated with passed in CAS username.
   *
   * @param string $cas_username
   *   The CAS username to lookup.
   *
   * @return bool|int
   *   The uid of the user associated with the $cas_username, FALSE otherwise.
   */
  public function getUidForCasUsername($cas_username) {
    return $this->authmap->getUid($cas_username, $this->provider);
  }

  /**
   * Save an association of the passed in Drupal user account and CAS username.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   * @param string $cas_username
   *   The CAS username.
   */
  public function setCasUsernameForAccount(UserInterface $account, $cas_username) {
    $this->authmap->save($account, $this->provider, $cas_username);
  }

  /**
   * Remove the CAS username association with the provided user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account entity.
   */
  public function removeCasUsernameForAccount(UserInterface $account) {
    $this->authmap->delete($account->id(), $this->provider);
  }

  /**
   * Generate a random password for new user registrations.
   *
   * @return string
   *   A random password.
   */
  protected function randomPassword() {
    // Default length is 10, use a higher number that's harder to brute force.
    return $this->passwordGenerator->generate(30);
  }

  /**
   * Return the email address that should be assigned to an auto-register user.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag associated with the user's login attempt.
   *
   * @return string
   *   The email address.
   *
   * @throws \Drupal\cas\Exception\CasLoginException
   *   Thrown when the email address cannot be derived properly.
   */
  public function getEmailForNewAccount(CasPropertyBag $cas_property_bag) {
    $email_assignment_strategy = $this->settings->get('cas.settings')->get('user_accounts.email_assignment_strategy');
    if ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_STANDARD) {
      return $cas_property_bag->getUsername() . '@' . $this->settings->get('cas.settings')->get('user_accounts.email_hostname');
    }
    elseif ($email_assignment_strategy === self::EMAIL_ASSIGNMENT_ATTRIBUTE) {
      $email_attribute = $this->settings->get('cas.settings')->get('user_accounts.email_attribute');
      if (empty($email_attribute) || !array_key_exists($email_attribute, $cas_property_bag->getAttributes())) {
        throw new CasLoginException('Specified CAS email attribute does not exist.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      $val = $cas_property_bag->getAttributes()[$email_attribute];
      if (empty($val)) {
        throw new CasLoginException('Empty data found for CAS email attribute.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      // The attribute value may actually be an array of values, but we need it
      // to only contain 1 value.
      if (is_array($val) && count($val) !== 1) {
        throw new CasLoginException('Specified CAS email attribute was formatted in an unexpected way.', CasLoginException::ATTRIBUTE_PARSING_ERROR);
      }

      if (is_array($val)) {
        $val = $val[0];
      }

      return trim($val);
    }
    else {
      throw new CasLoginException('Invalid email address assignment type for auto user registration specified in settings.');
    }
  }

  /**
   * Checks whether Drupal requires admin approval when registering new users.
   *
   * @return bool
   *   Whether Drupal requires admin approval when registering new users.
   */
  protected function isAdminApprovalNeeded(): bool {
    if (!isset($this->adminApprovalNeeded)) {
      $cas_settings = $this->settings->get('cas.settings');
      $user_settings = $this->settings->get('user.settings');
      $this->adminApprovalNeeded = $cas_settings->get('user_accounts.auto_register_follow_registration_policy') && $user_settings->get('register') === UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL;
    }
    return $this->adminApprovalNeeded;
  }

}
