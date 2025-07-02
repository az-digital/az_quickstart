<?php

namespace Drupal\views_bulk_operations\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Defines view data service for Views Bulk Operations.
 */
interface ViewsBulkOperationsViewDataInterface {

  /**
   * Initialize additional variables.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view object.
   * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $display
   *   The current display plugin.
   * @param string $relationship
   *   Relationship ID.
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, $relationship): void;

  /**
   * Get entity type IDs.
   *
   * @return array
   *   Array of entity type IDs.
   */
  public function getEntityTypeIds(): array;

  /**
   * Get view provider.
   *
   * @return string
   *   View provider ID.
   */
  public function getViewProvider(): string;

  /**
   * Get base field for the current view.
   *
   * @return string
   *   The base field name.
   */
  public function getViewBaseField(): string;

  /**
   * Get entity from views row.
   *
   * @param \Drupal\views\ResultRow $row
   *   Views row object.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   An entity object.
   */
  public function getEntity(ResultRow $row): ?EntityInterface;

  /**
   * Get the total count of results on all pages.
   *
   * @param bool $clear_on_exposed
   *   Are we clearing selection on exposed filters change?
   *
   * @return int
   *   The total number of results this view displays or null if undetermined.
   */
  public function getTotalResults($clear_on_exposed): ?int;

  /**
   * The default entity getter function.
   *
   * Must work well with standard Drupal core entity views.
   *
   * @param \Drupal\views\ResultRow $row
   *   Views result row.
   * @param string $relationship_id
   *   Id of the view relationship.
   * @param \Drupal\views\ViewExecutable $view
   *   The current view object.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The translated entity.
   */
  public function getEntityDefault(ResultRow $row, $relationship_id, ViewExecutable $view): ?FieldableEntityInterface;

  /**
   * Get entity data array for this view results.
   *
   * @return array<string, string>
   *   Bulk form keys and labels keyed by row index.
   */
  public function getViewEntityData(): array;

}
