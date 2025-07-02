<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Events;

use Drupal\Component\EventDispatcher\Event;
use Drupal\views\ViewExecutable;

/**
 * Event dispatched to attach entities to a remote View.
 */
final class RemoteDataLoadEntitiesEvent extends Event {

  /**
   * The view.
   *
   * @var \Drupal\views\ViewExecutable
   */
  private ViewExecutable $view;

  /**
   * The results.
   *
   * @var \Drupal\views\ResultRow[]
   */
  private array $results;

  /**
   * Constructs a new RemoteDataLoadEntitiesEvent object.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param \Drupal\views\ResultRow[] $results
   *   The results.
   */
  public function __construct(ViewExecutable $view, array $results) {
    $this->view = $view;
    $this->results = $results;
  }

  /**
   * Get the View dispatching the event.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view.
   */
  public function getView(): ViewExecutable {
    return $this->view;
  }

  /**
   * Get the results.
   *
   * @return \Drupal\views\ResultRow[]
   *   The results.
   */
  public function getResults(): array {
    return $this->results;
  }

}
