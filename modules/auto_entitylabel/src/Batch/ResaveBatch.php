<?php

namespace Drupal\auto_entitylabel\Batch;

use Drupal\Core\Entity\EntityInterface;

/**
 * Processes entities in chunks to re-save their labels.
 *
 * @package Drupal\auto_entitylabel\Batch
 */
class ResaveBatch {

  /**
   * {@inheritdoc}
   */
  public static function batchOperation(array $chunk, array $bundle, array &$context) {

    $entity_manager = \Drupal::service('entity_type.manager');

    foreach ($chunk as $id) {
      $entity = $entity_manager->getStorage($bundle[0])->load($id);
      if (!empty($entity) && $entity instanceof EntityInterface) {
        $entity->save();
      }

      $context['results'][] = $id;
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function batchFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('Resaved @count labels.', [
        '@count' => count($results),
      ]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

}
