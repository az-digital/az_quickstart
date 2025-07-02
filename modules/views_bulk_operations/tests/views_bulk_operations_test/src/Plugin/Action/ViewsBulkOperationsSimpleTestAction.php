<?php

namespace Drupal\views_bulk_operations_test\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * Action for test purposes only.
 */
#[Action(
  id: 'views_bulk_operations_simple_test_action',
  label: new TranslatableMarkup('VBO simple test action'),
  type: 'node'
)]
class ViewsBulkOperationsSimpleTestAction extends ViewsBulkOperationsActionBase {
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->messenger()->addMessage(\sprintf('Test action (label: %s)',
      $entity->label()
    ));
    return $this->t('Test');
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
