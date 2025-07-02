<?php

namespace Drupal\externalauth;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\externalauth\Event\ExternalAuthAuthmapAlterEvent;
use Drupal\externalauth\Event\ExternalAuthEvents;
use Drupal\externalauth\Event\ExternalAuthLoginEvent;
use Drupal\externalauth\Event\ExternalAuthRegisterEvent;
use Drupal\externalauth\Exception\ExternalAuthRegisterException;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Service to handle external authentication logic.
 */
class ExternalAuth implements ExternalAuthInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param AuthmapInterface $authmap
   *   The authmap service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AuthmapInterface $authmap, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher) {
    $this->entityTypeManager = $entity_type_manager;
    $this->authmap = $authmap;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function load(string $authname, string $provider) {
    if ($uid = $this->authmap->getUid($authname, $provider)) {
      return $this->entityTypeManager->getStorage('user')->load($uid);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function login(string $authname, string $provider) {
    $account = $this->load($authname, $provider);
    if ($account) {
      return $this->userLoginFinalize($account, $authname, $provider);
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function register(string $authname, string $provider, array $account_data = [], $authmap_data = NULL) {
    if (!empty($account_data['name'])) {
      $username = $account_data['name'];
      unset($account_data['name']);
    }
    else {
      $username = $provider . '_' . $authname;
    }

    $authmap_event = $this->eventDispatcher->dispatch(new ExternalAuthAuthmapAlterEvent($provider, $authname, $username, $authmap_data), ExternalAuthEvents::AUTHMAP_ALTER);
    $entity_storage = $this->entityTypeManager->getStorage('user');

    $account_search = $entity_storage->loadByProperties(['name' => $authmap_event->getUsername()]);
    if ($account = reset($account_search)) {
      throw new ExternalAuthRegisterException(sprintf('User could not be registered. There is already an account with username "%s"', $authmap_event->getUsername()));
    }

    // Set up the account data to be used for the user entity.
    $account_data = array_merge(
      [
        'name' => $authmap_event->getUsername(),
        'init' => $provider . '_' . $authmap_event->getAuthname(),
        'status' => 1,
        'access' => 0,
      ],
      $account_data
    );
    $account = $entity_storage->create($account_data);

    $account->enforceIsNew();
    $account->save();
    $this->authmap->save($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData());
    $this->eventDispatcher->dispatch(new ExternalAuthRegisterEvent($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData()), ExternalAuthEvents::REGISTER);
    $this->logger->notice('External registration of user %name from provider %provider and authname %authname',
      [
        '%name' => $account->getAccountName(),
        '%provider' => $provider,
        '%authname' => $authname,
      ]
    );

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function loginRegister(string $authname, string $provider, array $account_data = [], $authmap_data = NULL) {
    $account = $this->login($authname, $provider);
    if (!$account) {
      $account = $this->register($authname, $provider, $account_data, $authmap_data);
      return $this->userLoginFinalize($account, $authname, $provider);
    }
    return $account;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public function userLoginFinalize(UserInterface $account, string $authname, string $provider): UserInterface {
    user_login_finalize($account);
    $this->logger->notice('External login of user %name', ['%name' => $account->getAccountName()]);
    $this->eventDispatcher->dispatch(new ExternalAuthLoginEvent($account, $provider, $authname), ExternalAuthEvents::LOGIN);
    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function linkExistingAccount(string $authname, string $provider, UserInterface $account, $authmap_data = NULL) {
    // If a mapping (for the same provider) to this account already exists, and
    // the authname is the same, we silently skip saving this auth mapping.
    $current_authname = $this->authmap->get($account->id(), $provider);
    if ($current_authname === $authname) {
      return FALSE;
    }

    // If we update the authmap entry, let's log the change.
    if (!empty($current_authname)) {
      $this->logger->debug('Authmap change (%old => %new) for user %name with uid %uid from provider %provider', [
        '%old' => $current_authname,
        '%new' => $authname,
        '%name' => $account->getAccountName(),
        '%uid' => $account->id(),
        '%provider' => $provider,
      ]);
    }

    $authmap_event = $this->eventDispatcher->dispatch(new ExternalAuthAuthmapAlterEvent($provider, $authname, $account->getAccountName(), $authmap_data), ExternalAuthEvents::AUTHMAP_ALTER);
    $this->authmap->save($account, $provider, $authmap_event->getAuthname(), $authmap_event->getData());
    return TRUE;
  }

}
