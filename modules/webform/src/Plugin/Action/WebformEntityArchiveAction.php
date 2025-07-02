<?php

namespace Drupal\webform\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Archives a webform.
 *
 * @Action(
 *   id = "webform_archive_action",
 *   label = @Translation("Archive webform"),
 *   type = "webform"
 * )
 */
class WebformEntityArchiveAction extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\webform\WebformInterface $entity */
    $entity->set('archive', TRUE)->save();
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
