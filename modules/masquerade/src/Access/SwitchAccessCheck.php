<?php

namespace Drupal\masquerade\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\masquerade\Masquerade;

/**
 * Checks access for any masquerade permissions.
 */
class SwitchAccessCheck implements AccessInterface {

  /**
   * The masquerade service.
   *
   * @var \Drupal\masquerade\Masquerade
   */
  protected $masquerade;

  /**
   * Constructs a new UnmasqueradeAccessCheck object.
   *
   * @param \Drupal\masquerade\Masquerade $masquerade
   *   The masquerade service.
   */
  public function __construct(Masquerade $masquerade) {
    $this->masquerade = $masquerade;
  }

  /**
   * Check to see if user has any permissions to masquerade.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($this->masquerade->isMasquerading()) {
      // Do now allow to masquerade when already masquerading.
      $result = AccessResult::forbidden();
    }
    elseif ($account->id() == 1) {
      // Uid 1 may masquerade as anyone.
      $result = AccessResult::allowed();
    }
    else {
      // Ability to masquerade defined by permissions.
      $permissions = $this->masquerade->getPermissions();
      $result = AccessResult::allowedIfHasPermissions($account, $permissions, 'OR');
    }
    return $result->addCacheContexts(['session.is_masquerading']);
  }

}
