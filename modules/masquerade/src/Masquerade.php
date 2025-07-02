<?php

namespace Drupal\masquerade;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\PermissionHandlerInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Defines a masquerade service to switch user account.
 */
class Masquerade {
  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The session manager.
   *
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  protected $sessionManager;

  /**
   * The logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The permission handler.
   *
   * @var \Drupal\user\PermissionHandlerInterface
   */
  protected $permissionHandler;

  /**
   * Constructs Masquerade object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   *   The session manager.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   * @param \Drupal\user\PermissionHandlerInterface $permission_handler
   *   The permission handler.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, SessionManagerInterface $session_manager, SessionInterface $session, LoggerInterface $logger, PermissionHandlerInterface $permission_handler) {
    $this->currentUser = $current_user;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->moduleHandler = $module_handler;
    $this->sessionManager = $session_manager;
    $this->logger = $logger;
    $this->permissionHandler = $permission_handler;
    $this->session = $session;
  }

  /**
   * Logs out current user and logs in as pointed user.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity to switch to.
   *
   * @return \Drupal\user\UserInterface
   *   The previous user entity.
   *
   * @see \Drupal\Core\Session\SessionHandler::write()
   * @see \Drupal\user\Authentication\Provider\Cookie::getUserFromSession()
   */
  protected function switchUser(UserInterface $user) {
    /** @var \Drupal\user\UserInterface $previous */
    $previous = $this->userStorage->load($this->currentUser->id());
    // Call logout hooks when switching from original user.
    $this->moduleHandler->invokeAll('user_logout', [$previous]);

    // Regenerate the session ID to prevent against session fixation attacks.
    $this->sessionManager->regenerate();

    // Supposed "safe" user switch method https://www.drupal.org/node/218104
    // @todo Use `Drupal::service('account_switcher')` but care about session.
    $this->currentUser->setAccount($user);
    $this->session->set('uid', $user->id());

    // Call all login hooks when making user login.
    $this->moduleHandler->invokeAll('user_login', [$user]);
    return $previous;
  }

  /**
   * Returns the masquerading identifier.
   *
   * @return string|null
   *   The per-session masquerade identifier or null when no value is set.
   *
   * @see \Drupal\masquerade\Session\MetadataBag::getMasquerade()
   */
  protected function getMasquerade() {
    // Accessing metadata does not try to start session.
    return $this->session->getMetadataBag()->getMasquerade();
  }

  /**
   * Returns whether the current user is masquerading.
   *
   * @return bool
   *   TRUE when already masquerading, FALSE otherwise.
   */
  public function isMasquerading() {
    return (bool) $this->getMasquerade();
  }

  /**
   * Masquerades the current user as a given user.
   *
   * @param \Drupal\user\UserInterface $target_account
   *   The user account object to masquerade as.
   *
   * @return bool
   *   TRUE when masqueraded, FALSE otherwise.
   *
   * @see \Drupal\masquerade\Session\MetadataBag::setMasquerade()
   */
  public function switchTo(UserInterface $target_account) {

    // Save previous account ID to session storage, set this before
    // switching so that other modules can react to it, e.g. during
    // hook_user_logout().
    $this->session->getMetadataBag()->setMasquerade($this->currentUser->id());

    $account = $this->switchUser($target_account);

    $this->logger->info('User %username masqueraded as %target_username.', [
      '%username' => $account->getDisplayName(),
      '%target_username' => $target_account->getDisplayName(),
      'link' => $target_account->toLink($this->t('view'))->toString(),
    ]);
    return TRUE;
  }

  /**
   * Switching back to previous user.
   *
   * @return bool
   *   TRUE when switched back, FALSE otherwise.
   *
   * @see \Drupal\masquerade\Session\MetadataBag::clearMasquerade()
   */
  public function switchBack() {
    if (!$this->isMasquerading()) {
      return FALSE;
    }
    // Load previous user account.
    $user = $this->userStorage->load($this->getMasquerade());
    if (!$user) {
      // Ensure the flag is cleared.
      $this->session->remove('masquerading');
      // User could be canceled while masquerading.
      return FALSE;
    }

    $account = $this->switchUser($user);

    // Clear the masquerading flag after switching the user so that hook
    // implementations can differentiate this from a real logout/login.
    $this->session->getMetadataBag()->clearMasquerade();

    $this->logger->info('User %username stopped masquerading as %old_username.', [
      '%username' => $user->getDisplayName(),
      '%old_username' => $account->getDisplayName(),
      'link' => $user->toLink($this->t('view'))->toString(),
    ]);
    return TRUE;
  }

  /**
   * Returns module provided permissions.
   *
   * @return array
   *   Array of permission names.
   */
  public function getPermissions() {
    $permissions = [];
    foreach ($this->permissionHandler->getPermissions() as $name => $permission) {
      if ($permission['provider'] === 'masquerade') {
        // Filter only module's permissions.
        $permissions[] = $name;
      }
    }
    return $permissions;
  }

}
