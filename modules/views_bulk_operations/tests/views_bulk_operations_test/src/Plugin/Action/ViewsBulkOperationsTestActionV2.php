<?php

namespace Drupal\views_bulk_operations_test\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Action for test purposes only.
 */
#[Action(
  id: 'views_bulk_operations_test_action_v2',
  label: new TranslatableMarkup('VBO V2 API test action'),
  type: ''
)]
class ViewsBulkOperationsTestActionV2 extends ViewsBulkOperationsActionBase {
  use MessengerTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($entity->label() === 'Title 1') {
      $output = [
        'message' => $this->t('A warning message.'),
        'type' => 'warning',
      ];
    }
    else {
      $output = $this->t('Standard output.');
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $object->access('update', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public static function finished($success, array $results, array $operations): ?RedirectResponse {
    // Let's return a bit different message. We don't except faliures
    // in tests as well so no need to check for a success.
    $details = [];
    foreach ($results['operations'] as $operation) {
      $details[] = $operation['message'] . ' (' . $operation['count'] . ')';
    }
    $message = static::translate('Custom processing message: @operations.', [
      '@operations' => \implode(', ', $details),
    ]);
    static::message($message);
    return NULL;
  }

}
