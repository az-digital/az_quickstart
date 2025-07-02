<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;

/**
 * Opens a webform.
 *
 * @Action(
 *   id = "webform_open_action",
 *   label = @Translation("Open webform"),
 *   type = "webform"
 * )
 */
class WebformEntityOpenAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\webform\WebformInterface $entity */
    $entity->setStatus(WebformInterface::STATUS_OPEN)->save();
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
