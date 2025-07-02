<?php

declare(strict_types=1);

namespace Drupal\flag_test_plugins\Plugin\Flag;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Drupal\flag\Plugin\Flag\EntityFlagType;

/**
 * Test flag type plugin which denies access.
 *
 * @FlagType(
 *   id = "test_access_denied",
 *   title = @Translation("Flag type plugin which denies access."),
 *   entity_type = "node",
 * )
 */
class AccessDenied extends EntityFlagType {

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
    if (empty($flaggable)) {
      // Grant access if the method is called with no flaggable parameter. This
      // is to ensure that access in the code being tested is properly passing
      // the flaggable entity and not only relying on the general access case.
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
