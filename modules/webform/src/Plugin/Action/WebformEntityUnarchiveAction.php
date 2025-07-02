<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unarchives a webform.
 *
 * @Action(
 *   id = "webform_unarchive_action",
 *   label = @Translation("Restore webform"),
 *   type = "webform"
 * )
 */
class WebformEntityUnarchiveAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\webform\WebformInterface $entity */
    $entity->set('archive', FALSE)->save();
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
