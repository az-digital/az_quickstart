<?php

namespace Drupal\webform_options_limit\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\webform\Access\WebformEntityAccess;
use Drupal\webform\WebformInterface;
use Drupal\webform_node\Access\WebformNodeAccess;
use Drupal\webform_options_limit\Plugin\WebformOptionsLimitHandlerInterface;

/**
 * Defines the custom access control handler for the webform options limits.
 */
class WebformOptionsLimitAccess {

  /**
   * Check whether the webform option limits summary.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkAccess(WebformInterface $webform) {
    if (!static::hasOptionsLimit($webform)) {
      return AccessResult::forbidden()->addCacheableDependency($webform);
    }

    return WebformEntityAccess::checkResultsAccess($webform);
  }

  /**
   * Check whether the user can access a node's webform options limits summary.
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

    if (!static::hasOptionsLimit($webform)) {
      return AccessResult::forbidden()->addCacheableDependency($webform);
    }

    return WebformNodeAccess::checkWebformResultsAccess($operation, $entity_access, $node, $account);
  }

  /**
   * Determine if the webform has an options limit handler.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return bool
   *   TRUE if the webform has an options limit handler.
   */
  protected static function hasOptionsLimit(WebformInterface $webform) {
    $handlers = $webform->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformOptionsLimitHandlerInterface) {
        $limit_user = $handler->getSetting('limit_user');
        if (empty($limit_user)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
