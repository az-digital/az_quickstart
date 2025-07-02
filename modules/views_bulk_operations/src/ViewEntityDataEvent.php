<?php

namespace Drupal\views_bulk_operations;

use Drupal\Component\EventDispatcher\Event;
use Drupal\views\ViewExecutable;

/**
 * Defines Views Bulk Operations event type.
 */
class ViewEntityDataEvent extends Event {

  public const NAME = 'views_bulk_operations.view_entity_data';

  /**
   * Entity data array - bulk form keys and labels keyed by row index.
   *
   * @var array<string, string>
   */
  protected array $viewEntityData = [];

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
   * Get view entity data.
   *
   * @return array<string, string>
   *   See ViewsBulkOperationsViewDataInterface::getViewEntityData().
   */
  public function getViewEntityData(): array {
    return $this->viewEntityData;
  }

  /**
   * Set view entity data.
   *
   * @param array<string, string> $data
   *   See ViewsBulkOperationsViewDataInterface::getViewEntityData().
   */
  public function setViewEntityData(array $data) {
    $this->viewEntityData = $data;
  }

}
