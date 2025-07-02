<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;

/**
 * Closes a webform.
 *
 * @Action(
 *   id = "webform_close_action",
 *   label = @Translation("Close webform"),
 *   type = "webform"
 * )
 */
class WebformEntityCloseAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\webform\WebformInterface $entity */
    $entity->setStatus(WebformInterface::STATUS_CLOSED)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\webform\WebformInterface $object */
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
