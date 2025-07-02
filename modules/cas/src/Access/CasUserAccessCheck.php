<?php

namespace Drupal\cas\Access;

use Drupal\cas\Service\CasUserManager;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access checker that restricts CAS users for certain routes.
 */
class CasUserAccessCheck implements AccessInterface {

  /**
   * The CAS settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The CAS user manager service.
   *
   * @var \Drupal\cas\Service\CasUserManager
   */
  protected $casUserManager;

  /**
   * Constructs a new access checker instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\cas\Service\CasUserManager $cas_user_manager
   *   The CAS user manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CasUserManager $cas_user_manager) {
    $this->settings = $config_factory->get('cas.settings');
    $this->casUserManager = $cas_user_manager;
  }

  /**
   * Checks the access to routes tagged with '_cas_user_access'.
   *
   * If the current user account is linked to a CAS account and the setting
   * 'restrict_password_management' is TRUE, deny the access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result object.
   */
  public function access(AccountInterface $account) {
    if (
      // The 'user_accounts.restrict_password_management' is FALSE.
      !$this->settings->get('user_accounts.restrict_password_management')
      // Or the route is accessed by an anonymous users.
      || $account->isAnonymous()
      // Or the user doesn't have a linked CAS account.
      || !$this->casUserManager->getCasUsernameForAccount($account->id())
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden("Is logged in CAS user and 'restrict_password_management' is TRUE");
  }

}
