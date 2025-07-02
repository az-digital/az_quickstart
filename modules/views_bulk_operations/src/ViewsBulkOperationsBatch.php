<?php

namespace Drupal\views_bulk_operations;

use Drupal\Core\Url;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionCompletedTrait;

/**
 * Defines module Batch API methods.
 */
class ViewsBulkOperationsBatch {

  use ViewsBulkOperationsActionCompletedTrait;

  /**
   * Gets the list of entities to process.
   *
   * Used in "all results" batch operation.
   *
   * @param array $data
   *   Processed view data.
   * @param array $context
   *   Batch context.
   */
  public static function getList(array $data, array &$context): void {
    // Initialize batch.
    if (empty($context['sandbox'])) {
      $context['sandbox']['processed'] = 0;
      $context['sandbox']['page'] = 0;
      $context['sandbox']['total'] = $data['exclude_mode'] ? $data['total_results'] - \count($data['exclude_list']) : $data['total_results'];
      $context['sandbox']['npages'] = \ceil($data['total_results'] / $data['batch_size']);
      $context['results'] = $data;
    }

    $actionProcessor = \Drupal::service('views_bulk_operations.processor');
    $actionProcessor->initialize($data);

    // Populate queue.
    $list = $actionProcessor->getPageList($context['sandbox']['page']);
    $count = \count($list);

    foreach ($list as $item) {
      $context['results']['list'][] = $item;
    }

    $context['sandbox']['page']++;
    $context['sandbox']['processed'] += $count;

    if ($context['sandbox']['page'] <= $context['sandbox']['npages']) {
      $context['finished'] = 0;
      $context['finished'] = $context['sandbox']['processed'] / $context['sandbox']['total'];
      $context['message'] = static::translate('Prepared @count of @total entities for processing.', [
        '@count' => $context['sandbox']['processed'],
        '@total' => $context['sandbox']['total'],
      ]);
    }

  }

  /**
   * Save generated list to user tempstore.
   *
   * @param bool $success
   *   Was the process successful?
   * @param array $results
   *   Batch process results array.
   * @param array $operations
   *   Performed operations array.
   */
  public static function saveList($success, array $results, array $operations): void {
    if ($success) {
      $results['redirect_url'] = $results['redirect_after_processing'];
      unset($results['redirect_after_processing']);
      $tempstore_factory = \Drupal::service('tempstore.private');
      $current_user = \Drupal::service('current_user');
      $tempstore_name = 'views_bulk_operations_' . $results['view_id'] . '_' . $results['display_id'];
      $results['prepopulated'] = TRUE;
      $tempstore_factory->get($tempstore_name)->set($current_user->id(), $results);
    }
  }

  /**
   * Batch operation callback.
   *
   * @param array $data
   *   Processed view data.
   * @param array $context
   *   Batch context.
   */
  public static function operation(array $data, array &$context): void {
    // Initialize batch.
    if (empty($context['sandbox'])) {
      $context['sandbox']['processed'] = 0;
      $context['results']['operations'] = [];
      $context['sandbox']['page'] = 0;
      $context['sandbox']['npages'] = \ceil($data['total_results'] / $data['batch_size']);
    }

    // Get entities to process.
    $actionProcessor = \Drupal::service('views_bulk_operations.processor');
    $actionProcessor->initialize($data);

    // Do the processing.
    $count = $actionProcessor->populateQueue($data, $context);

    $batch_results = $actionProcessor->process();
    $context['results'] = $actionProcessor->processResults($batch_results, $context['results']);

    $context['sandbox']['processed'] += $count;
    $context['sandbox']['page']++;

    if ($context['sandbox']['page'] <= $context['sandbox']['npages']) {
      $context['finished'] = 0;

      $context['finished'] = $context['sandbox']['processed'] / $context['sandbox']['total'];
      $context['message'] = static::translate('Processed @count of @total entities.', [
        '@count' => $context['sandbox']['processed'],
        '@total' => $context['sandbox']['total'],
      ]);
    }
  }

  /**
   * Batch builder function.
   *
   * @param array $view_data
   *   Processed view data.
   *
   * @return mixed[]
   *   Batch API batch definition.
   */
  public static function getBatch(array &$view_data): array {
    $current_class = static::class;

    // Prepopulate results.
    if (empty($view_data['list'])) {
      // Redirect this batch to the processing URL and set
      // previous redirect under a different key for later use.
      $view_data['redirect_after_processing'] = $view_data['redirect_url'];
      $view_data['redirect_url'] = Url::fromRoute('views_bulk_operations.execute_batch', [
        'view_id' => $view_data['view_id'],
        'display_id' => $view_data['display_id'],
      ]);

      $batch = [
        'title' => static::translate('Prepopulating entity list for processing.'),
        'operations' => [
          [
            [$current_class, 'getList'],
            [$view_data],
          ],
        ],
        'progress_message' => static::translate('Prepopulating, estimated time left: @estimate, elapsed: @elapsed.'),
        'finished' => [$current_class, 'saveList'],
      ];
    }

    // Execute action.
    else {
      $batch = [
        'title' => static::translate('Performing @operation on selected entities.', ['@operation' => $view_data['action_label']]),
        'operations' => [
          [
            [$current_class, 'operation'],
            [$view_data],
          ],
        ],
        'progress_message' => static::translate('Processing, estimated time left: @estimate, elapsed: @elapsed.'),
        'finished' => $view_data['finished_callback'],
      ];
    }

    return $batch;
  }

}
