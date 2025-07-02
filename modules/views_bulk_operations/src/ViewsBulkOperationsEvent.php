<?php

namespace Drupal\views_bulk_operations;

use Drupal\Component\EventDispatcher\Event;
use Drupal\views\ViewExecutable;

/**
 * Defines Views Bulk Operations event type.
 */
class ViewsBulkOperationsEvent extends Event {

  public const NAME = 'views_bulk_operations.view_data';

  /**
   * IDs of entity types returned by the view.
   *
   * @var array
   */
  protected array $entityTypeIds = [];

  /**
   * Row entity getter information.
   *
   * @var array
   */
  protected array $entityGetter = [];

  /**
   * Object constructor.
   *
   * @param string $provider
   *   The provider of the current view.
   * @param array $viewData
   *   The views data of the current view.
   * @param \Drupal\views\ViewExecutable $view
   *   The current view.
   */
  public function __construct(
    protected string $provider,
    protected array $viewData,
    protected ViewExecutable $view
  ) {}

  /**
   * Get view provider.
   *
   * @return string
   *   The view provider
   */
  public function getProvider(): string {
    return $this->provider;
  }

  /**
   * Get view data.
   *
   * @return array
   *   The current view data
   */
  public function getViewData(): array {
    return $this->viewData;
  }

  /**
   * Get current view.
   *
   * @return \Drupal\views\ViewExecutable
   *   The current view object
   */
  public function getView(): ViewExecutable {
    return $this->view;
  }

  /**
   * Get entity type IDs displayed by the current view.
   *
   * @return array
   *   Entity type IDs.
   */
  public function getEntityTypeIds(): array {
    return $this->entityTypeIds;
  }

  /**
   * Get entity getter callable.
   *
   * @return array
   *   Entity getter information.
   */
  public function getEntityGetter(): array {
    return $this->entityGetter;
  }

  /**
   * Set entity type IDs.
   *
   * @param array $entityTypeIds
   *   Entity type IDs.
   */
  public function setEntityTypeIds(array $entityTypeIds): void {
    $this->entityTypeIds = $entityTypeIds;
  }

  /**
   * Set entity getter callable.
   *
   * @param array $entityGetter
   *   Entity getter information.
   */
  public function setEntityGetter(array $entityGetter): void {
    if (!isset($entityGetter['callable'])) {
      throw new \Exception('Views Bulk Operations entity getter callable is not defined.');
    }
    $this->entityGetter = $entityGetter;
  }

}
