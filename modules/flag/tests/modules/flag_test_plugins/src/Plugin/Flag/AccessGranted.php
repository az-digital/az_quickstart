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
 *   id = "test_access_granted",
 *   title = @Translation("Flag type plugin which always grants access."),
 *   entity_type = "node",
 * )
 */
class AccessGranted extends EntityFlagType {

  /**
   * {@inheritdoc}
   */
  public function actionAccess($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
    return AccessResult::allowed();
  }

}
