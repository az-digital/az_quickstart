<?php

namespace Drupal\views_bulk_operations\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines Views Bulk Operations action processor.
 */
interface ViewsBulkOperationsActionProcessorInterface {

  /**
   * Set values.
   *
   * @param array $view_data
   *   Data concerning the view that will be processed.
   * @param mixed $view
   *   The current view object or NULL.
   */
  public function initialize(array $view_data, $view = NULL): void;

  /**
   * Get the current processing entity queue.
   *
   * @param array $view_data
   *   Data concerning the view that will be processed.
   *
   * @return array
   *   Array of entity labels.
   */
  public function getLabels(array $view_data): array;

  /**
   * Get full list of items from a specific view page.
   *
   * @param int $page
   *   Results page number.
   *
   * @return array
   *   Array of result data arrays.
   */
  public function getPageList($page): array;

  /**
   * Populate entity queue for processing.
   *
   * @param array $data
   *   Data concerning the view that will be processed.
   * @param array $context
   *   Batch API context.
   */
  public function populateQueue(array $data, array &$context = []): int;

  /**
   * Process queue.
   *
   * @return mixed[]
   *   Array of individual results.
   */
  public function process(): array;

  /**
   * Process results.
   *
   * Merges multiple individual operation results into one or more containing
   * counts.
   *
   * @param mixed[] $results
   *   Individual results array.
   * @param mixed[] $previous
   *   Results from previous batches.
   *
   * @return mixed[]
   *   Array of processed results.
   */
  public function processResults(array $results, array $previous = []): array;

  /**
   * Helper function for processing results from view data.
   *
   * @param array $data
   *   Data concerning the view that will be processed.
   * @param mixed $view
   *   The current view object or NULL.
   */
  public function executeProcessing(array &$data, $view = NULL): RedirectResponse;

}
