<?php

declare(strict_types=1);

namespace Drupal\views_remote_data\Plugin\views\query;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Views query plugin for remote data retrieved over an API.
 *
 * We cannot use \Drupal\Core\Cache\RefinableCacheableDependencyTrait as the
 * parent class in Views does not, and we would conflict over the implemented
 * methods.
 *
 * @ViewsQuery(
 *   id = "views_remote_data_query",
 *   title = @Translation("Remote data query"),
 * )
 */
final class RemoteDataQuery extends QueryPluginBase {

  /**
   * Cache contexts.
   *
   * @var string[]
   */
  private $cacheContexts = [];

  /**
   * Cache tags.
   *
   * @var string[]
   */
  private $cacheTags = ['views_remote_data'];

  /**
   * Cache max-age.
   *
   * @var int
   */
  private $cacheMaxAge = Cache::PERMANENT;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->eventDispatcher = $container->get('event_dispatcher');
    return $instance;
  }

  /**
   * An array of conditions.
   *
   * @var array
   *
   * @note public to match the SQL plugin in Views
   */
  public array $where = [];

  /**
   * An array of sorts.
   *
   * @var array
   *
   * @note public to match the SQL plugin in Views
   */
  public $orderby = [];

  /**
   * The offset.
   *
   * @var int
   *
   * @note public to match the SQL plugin in Views
   */
  public int $offset = 0;

  /**
   * Group operator.
   *
   * @var string
   *
   * @note undocumented on base class, but always set and used in SQL.
   */
  protected $groupOperator = 'AND';

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view): void {
    parent::build($view);
    $view->initPager();

    // Let the pager modify the query to add limits.
    $view->pager->query();
  }

  /**
   * {@inheritdoc}
   */
  public function getLimit(): int {
    return (int) $this->limit;
  }

  /**
   * Gets the offset.
   *
   * @return int
   *   The offset.
   */
  public function getOffset(): int {
    return (int) $this->offset;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ViewExecutable $view): void {
    $view_id = $view->id();
    assert($view_id !== NULL);
    $pager = $view->pager;
    assert($pager instanceof PagerPluginBase);

    $start = microtime(TRUE);
    $event = new RemoteDataQueryEvent(
      $this->view,
      $this->where,
      $this->orderby,
      $this->getLimit(),
      $this->getOffset(),
    );
    $this->eventDispatcher->dispatch($event);

    $this->cacheContexts = Cache::mergeContexts($this->cacheContexts, $event->getCacheContexts());
    $this->cacheTags = Cache::mergeTags($this->cacheTags, $event->getCacheTags());
    $this->cacheMaxAge = $event->getCacheMaxAge();

    $view->result = $event->getResults();
    array_walk($view->result, static fn (ResultRow $row, $index) => $row->index = $index);

    $pager->postExecute($view->result);
    $pager->updatePageInfo();
    $view->total_rows = $view->pager->getTotalItems();

    $this->loadEntities($view->result);

    $view->execute_time = microtime(TRUE) - $start;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntities(&$results): void {
    $this->eventDispatcher->dispatch(
      new RemoteDataLoadEntitiesEvent($this->view, $results)
    );
  }

  /**
   * Undocumented method required by Views to ensure database table.
   *
   * This method does nothing, but is required as various handlers in Views will
   * try to call it, although it does not belong on an interface.
   *
   * @param string $table
   *   The table.
   * @param string $relationship
   *   The relationship.
   *
   * @return string
   *   The table alias.
   */
  public function ensureTable($table, $relationship = NULL): string {
    return '';
  }

  /**
   * Undocumented method required by Views to specify fields returned.
   *
   * This method does nothing, but is required as various handlers in Views will
   * try to call it, although it does not belong on an interface.
   *
   * @param string $table
   *   The table.
   * @param string $field
   *   The field's name.
   * @param string|null $alias
   *   The alias.
   * @param array $params
   *   The params.
   *
   * @return string
   *   The field's alias.
   *
   * @todo we could support this, passing to dispatched event. That way a
   *   JSON:API or GraphQL request would not need to hardcode requested fields.
   */
  public function addField(string $table, string $field, ?string $alias = '', array $params = []): string {
    return $field;
  }

  /**
   * Adds a condition.
   *
   * Undocumented method required by Views that does not belong to an interface.
   *
   * @param string $group
   *   The condition's group.
   * @param string $field
   *   The field.
   * @param string|null $value
   *   The value.
   * @param string|null $operator
   *   The operator.
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL): void {
    $this->where[$group]['conditions'][] = [
      'field' => explode('.', $field),
      'value' => $value,
      'operator' => $operator,
    ];
  }

  /**
   * Adds a sort.
   *
   * Undocumented method required by Views that does not belong to an interface.
   *
   * @param string $table
   *   The table (unused.)
   * @param string|null $field
   *   The field's name.
   * @param string $order
   *   The order.
   * @param string $alias
   *   The field alias (unused.)
   * @param array $params
   *   The params (unused.)
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', array $params = []): void {
    // In \Drupal\views\Plugin\views\field\FieldPluginBase::clickSort the field
    // is passed as `null` and only the alias is provided.
    if ($field === NULL) {
      $field = $alias;
    }
    $this->orderby[] = [
      'field' => explode('.', $field),
      'order' => $order,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $tags = [];
    foreach ($this->view->result as $row) {
      if ($row->_entity instanceof EntityInterface) {
        $tags = Cache::mergeTags($row->_entity->getCacheTags(), $tags);
      }
    }
    return Cache::mergeTags($tags, $this->cacheTags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return $this->cacheMaxAge;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), $this->cacheContexts);
  }

}
