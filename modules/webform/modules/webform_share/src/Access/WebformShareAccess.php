<?php

namespace Drupal\webform_share\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform_node\Access\WebformNodeAccess;

/**
 * Defines the custom access control handler for webform sharing.
 */
class WebformShareAccess {

  /**
   * Check whether the webform can be shared and it is not a template.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess(WebformInterface $webform) {
    $share = $webform->getSetting('share', TRUE);
    $share_node = \Drupal::moduleHandler()->moduleExists('webform_node')
      && $webform->getSetting('share_node', TRUE);
    $template = $webform->isTemplate();
    return AccessResult::allowedIf(($share || $share_node) && !$template)
      ->addCacheTags(['config:webform.settings'])
      ->addCacheableDependency($webform);
  }

  /**
   * Check whether the webform node can be shared.
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
  public static function checkNodeAccess($operation, $entity_access, NodeInterface $node, AccountInterface $account) {
    /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
    $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');
    $webform = $entity_reference_manager->getWebform($node);

    // Check that the node has a valid webform reference.
    if (!$webform) {
      return AccessResult::forbidden()->addCacheableDependency($node);
    }

    $share = $webform->getSetting('share_node', TRUE);
    if (!$share) {
      return AccessResult::forbidden()
        ->addCacheTags(['config:webform.settings'])
        ->addCacheableDependency($node)
        ->addCacheableDependency($webform);
    }

    return WebformNodeAccess::checkAccess($operation, $entity_access, $node, NULL, $account);
  }

}
