<?php

namespace Drupal\webform_node\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Access\WebformEntityAccess;
use Drupal\webform\Access\WebformSubmissionAccess;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the custom access control handler for the webform node.
 */
class WebformNodeAccess {

  /**
   * Check whether the user can access a node's webform drafts.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformDraftsAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    $access_result = static::checkAccess($operation, $entity_access, $node, NULL, $account);
    if ($access_result->isAllowed()) {
      /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
      $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
      $webform = $entity_reference_manager->getWebform($node);
      return WebformEntityAccess::checkDraftsAccess($webform, $node);
    }
    else {
      return $access_result;
    }
  }

  /**
   * Check whether the user can access a node's webform results.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformResultsAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    $access_result = static::checkAccess($operation, $entity_access, $node, NULL, $account);
    if ($access_result->isAllowed()) {
      /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
      $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
      $webform = $entity_reference_manager->getWebform($node);
      return WebformEntityAccess::checkResultsAccess($webform, $node);
    }
    else {
      return $access_result;
    }
  }

  /**
   * Check whether the user can access a node's webform log.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformLogAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    $access_result = static::checkWebformResultsAccess($operation, $entity_access, $node, $account);
    if (!$access_result->isAllowed()) {
      return $access_result;
    }

    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
    $webform = $entity_reference_manager->getWebform($node);
    if (!$webform->hasSubmissionLog()) {
      $access_result = AccessResult::forbidden();
    }

    return $access_result->addCacheableDependency($webform)->addCacheTags(['config:webform.settings']);
  }

  /**
   * Check whether the user can access a node's webform.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    return static::checkAccess($operation, $entity_access, $node, NULL, $account);
  }

  /**
   * Check whether the user can access a node's webform submission.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWebformSubmissionAccess($operation, $entity_access, NodeInterface $node, WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    $access_result = static::checkAccess($operation, $entity_access, $node, $webform_submission, $account);
    if (!$access_result->isAllowed()) {
      return $access_result;
    }

    switch ($operation) {
      case 'webform_submission_edit_all':
        return WebformSubmissionAccess::checkWizardPagesAccess($webform_submission);

      case 'webform_submission_resend':
        return WebformSubmissionAccess::checkResendAccess($webform_submission, $account);

      case 'webform_submission_duplicate':
        /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
        $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
        $webform = $entity_reference_manager->getWebform($node);
        return WebformEntityAccess::checkWebformSettingValue($webform, 'submission_user_duplicate', TRUE);
    }

    return $access_result;
  }

  /**
   * Check whether the user can access a node's webform and/or submission.
   *
   * @param string $operation
   *   Operation being performed.
   * @param string $entity_access
   *   Entity access rule that needs to be checked.
   * @param \Drupal\node\NodeInterface $node
   *   A node.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess($operation, $entity_access, NodeInterface $node, WebformSubmissionInterface $webform_submission = NULL, AccountInterface $account = NULL) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

    $webform = $entity_reference_manager->getWebform($node);
    // Check that the node has a valid webform reference.
    if (!$webform) {
      return AccessResult::forbidden();
    }

    // Check that the webform submission was created via the webform node.
    if ($webform_submission) {
      $source_node = $webform_submission->getSourceEntity();
      if (!$source_node || $source_node->id() !== $node->id()) {
        return AccessResult::forbidden();
      }
    }

    // Determine if this is a group node.
    $is_group_node = FALSE;
    if (\Drupal::moduleHandler()->moduleExists('webform_group')) {
      if (\Drupal::entityTypeManager()->hasDefinition('group_content')) {
        $is_group_node = \Drupal::entityTypeManager()->getStorage('group_content')->loadByEntity($node);
      }
      elseif (\Drupal::entityTypeManager()->hasDefinition('group_relation')) {
        $is_group_node = \Drupal::entityTypeManager()->getStorage('group_relation')->loadByEntity($node);
      }
    }

    // Check the node operation.
    if (!$operation) {
      $result = AccessResult::neutral();
    }
    elseif ($is_group_node && strpos($operation, 'webform_submission_') === 0) {
      // For group nodes, we need to bypass node access checking for
      // 'webform_submission_*' operations which trigger access forbidden.
      // @see group_entity_access()
      // @see https://www.drupal.org/project/webform/issues/3132204
      // @todo Add Webform node group permission provider w/ submission perms.
      $result = webform_node_node_access($node, $operation, $account);
    }
    else {
      $result = $node->access($operation, $account, TRUE);
    }

    // Check entity access.
    if ($entity_access) {
      // Check entity access for the webform.
      if (strpos($entity_access, 'webform.') === 0) {
        $result = $result->orIf($webform->access(str_replace('webform.', '', $entity_access), $account, TRUE));
      }
      // Check entity access for the webform submission.
      if (strpos($entity_access, 'webform_submission.') === 0) {
        $result = $result->orIf($webform_submission->access(str_replace('webform_submission.', '', $entity_access), $account, TRUE));
      }
    }

    return $result;
  }

}
