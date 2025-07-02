<?php

namespace Drupal\smart_date\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the smart date format entity type.
 *
 * @see \Drupal\smart_date\Entity\SmartDateFormat
 */
class SmartDateFormatAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {

    // There are no restrictions on viewing the label of a date format.
    if ($operation === 'view label') {
      return AccessResult::allowed();
    }
    elseif (in_array($operation, [
      'delete',
    ])) {
      if ($entity
        ->label() == 'default') {
        return AccessResult::forbidden('The SmartDateFormat config entity cannot be deleted.')
          ->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)
          ->addCacheableDependency($entity);
      }
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
