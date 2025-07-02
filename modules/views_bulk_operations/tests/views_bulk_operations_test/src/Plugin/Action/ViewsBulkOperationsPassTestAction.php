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
  id: 'views_bulk_operations_passing_test_action',
  label: new TranslatableMarkup('VBO parameters passing test action'),
  type: 'node'
)]
class ViewsBulkOperationsPassTestAction extends ViewsBulkOperationsActionBase {
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $nodes): array {
    if (!empty($this->context['sandbox'])) {
      $this->messenger()->addMessage(\sprintf(
        'Processed %s of %s.',
        $this->context['sandbox']['processed'],
        $this->context['sandbox']['total']
      ));
    }

    // Check if the passed view result rows contain the correct nodes.
    if (empty($this->context['sandbox']['result_pass_error'])) {
      $this->view->result = \array_values($this->view->result);
      foreach ($nodes as $index => $node) {
        $result_node = $this->view->result[$index]->_entity;
        if (
          $node->id() !== $result_node->id() ||
          $node->label() !== $result_node->label()
        ) {
          $this->context['sandbox']['result_pass_error'] = TRUE;
        }
      }
    }

    $batch_size = $this->context['sandbox']['batch_size'] ?? 0;
    $total = $this->context['sandbox']['total'] ?? 0;
    $processed = $this->context['sandbox']['processed'] ?? 0;

    // On last batch display message if passed rows match.
    if ($processed + $batch_size >= $total) {
      if (empty($this->context['sandbox']['result_pass_error'])) {
        $this->messenger()->addMessage('Passed view results match the entity queue.');
      }
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $this->executeMultiple([$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

}
