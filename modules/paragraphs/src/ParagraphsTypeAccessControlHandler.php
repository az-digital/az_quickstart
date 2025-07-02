<?php

namespace Drupal\paragraphs;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the Paragraphs type entity type.
 *
 * @see \Drupal\paragraphs\Entity\ParagraphsType
 */
class ParagraphsTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view label':
        return AccessResult::allowedIfHasPermission($account, 'access content');
      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
