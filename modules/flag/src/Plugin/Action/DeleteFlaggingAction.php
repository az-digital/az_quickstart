<?php

namespace Drupal\flag\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlaggingInterface;

/**
 * An action plugin to delete a flagging entity directly.
 *
 * @Action(
 *   id = "flag_delete_flagging",
 *   label = @Translation("Delete flagging (unflag)"),
 *   type = "flagging"
 * )
 */
class DeleteFlaggingAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\flag\FlaggingInterface $object */
    return $object->access('delete', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(?FlaggingInterface $flagging = NULL) {
    $flagging->delete();
  }

}
