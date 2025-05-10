<?php

namespace Drupal\az_cas_guest\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;

/**
 * Service for managing CAS guest authentication.
 */
class CasGuestManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new CasGuestManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory->get('az_cas_guest');
  }

  /**
   * Check if a Drupal user exists for the given CAS username.
   *
   * @param string $cas_username
   *   The CAS username.
   *
   * @return bool
   *   TRUE if a user exists.
   */
  public function userExists($cas_username) {
    // Check if a user exists with this CAS username.
    $users = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $cas_username]);
    
    return !empty($users);
  }

  /**
   * Get or create the shared guest account.
   *
   * @return \Drupal\user\UserInterface
   *   The guest user account.
   */
  public function getOrCreateGuestAccount() {
    $config = $this->configFactory->get('az_cas_guest.settings');
    $username = $config->get('guest_username') ?: 'cas_guest';
    
    // Try to load the existing account.
    $users = $this->entityTypeManager
      ->getStorage('user')
      ->loadByProperties(['name' => $username]);
    $guest_account = reset($users);
    
    // Create the account if it doesn't exist.
    if (!$guest_account) {
      // Generate a random password.
      $password = \Drupal::service('password_generator')->generate();
      
      $guest_account = User::create([
        'name' => $username,
        'mail' => $config->get('guest_email') ?: $username . '@example.com',
        'pass' => $password,
        'status' => 1,
      ]);
      $guest_account->save();
      
      // Add configured roles.
      $this->updateGuestAccountRoles($guest_account);
      
      $this->loggerFactory->notice('Created shared guest account: @username', [
        '@username' => $username,
      ]);
    }
    else {
      // Update roles in case configuration has changed.
      $this->updateGuestAccountRoles($guest_account);
    }
    
    return $guest_account;
  }

  /**
   * Update the roles for the guest account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account to update.
   */
  protected function updateGuestAccountRoles(UserInterface $account) {
    $config = $this->configFactory->get('az_cas_guest.settings');
    $roles = $config->get('guest_roles') ?: [];
    
    // Remove all roles except authenticated.
    foreach ($account->getRoles(TRUE) as $role) {
      $account->removeRole($role);
    }
    
    // Add configured roles.
    foreach ($roles as $role) {
      $account->addRole($role);
    }
    
    $account->save();
  }

}