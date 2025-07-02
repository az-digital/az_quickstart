<?php

namespace Drupal\access_unpublished;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for access tokens.
 */
class AccessTokenAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if (!$result->isNeutral()) {
      return $result;
    }

    switch ($operation) {
      case 'delete':
        if ($account->hasPermission('delete token')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->cachePerPermissions();

      case 'renew':
        if ($account->hasPermission('renew token')) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->cachePerPermissions();

      default:
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

}
