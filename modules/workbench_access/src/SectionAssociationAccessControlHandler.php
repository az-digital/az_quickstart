<?php

namespace Drupal\workbench_access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * The access control handler for the section_association entity type.
 *
 * @see \Drupal\workbench_access\Entity\SectionAssociation
 */
class SectionAssociationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // SectionAssociation is an internal entity type. Access is denied for
    // viewing, updating, and deleting.
    return AccessResult::forbidden('Section Association is an internal entity type.');
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // SectionAssociation is an internal entity type. Access is denied for
    // creating.
    return AccessResult::forbidden('Section Association is an internal entity type.');
  }

}
