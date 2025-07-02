<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Events;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyTrait;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Event dispatched to attach result rows to a remote View.
 */
final class RemoteDataQueryEvent extends Event implements RefinableCacheableDependencyInterface {

  use RefinableCacheableDependencyTrait;

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
  private array $results = [];

  /**
   * The conditions (filters, contextual filters.)
   *
   * @var array
   */
  private array $conditions;

  /**
   * The sorts.
   *
   * @var array
   */
  private array $sorts;

  /**
   * The limit per page.
   *
   * @var int
   */
  private int $limit;

  /**
   * The offset.
   *
   * @var int
   */
  private int $offset;

  /**
   * Constructs a new RemoteDataQueryEvent.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view.
   * @param array $conditions
   *   The conditions.
   * @param array $sorts
   *   The sorts.
   * @param int $limit
   *   The limit.
   * @param int $offset
   *   The offset.
   */
  public function __construct(ViewExecutable $view, array $conditions, array $sorts, int $limit, int $offset) {
    $this->view = $view;
    $this->conditions = $conditions;
    $this->sorts = $sorts;
    $this->limit = $limit;
    $this->offset = $offset;
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
   * Get the conditions.
   *
   * @return array
   *   The conditions.
   */
  public function getConditions(): array {
    return $this->conditions;
  }

  /**
   * Get the sorts.
   *
   * @return array
   *   The sorts.
   */
  public function getSorts(): array {
    return $this->sorts;
  }

  /**
   * Get the limit.
   *
   * @return int
   *   The limit.
   */
  public function getLimit(): int {
    return $this->limit;
  }

  /**
   * Get the offset.
   *
   * @return int
   *   The offset.
   */
  public function getOffset(): int {
    return $this->offset;
  }

  /**
   * Adds a result row.
   *
   * @param \Drupal\views\ResultRow $result
   *   The result row.
   */
  public function addResult(ResultRow $result): void {
    $this->results[] = $result;
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
