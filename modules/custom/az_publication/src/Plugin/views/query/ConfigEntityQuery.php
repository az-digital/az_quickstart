<?php

namespace Drupal\az_publication\Plugin\views\query;

use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\views\query\Sql;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * This query is able to work with config entities.
 *
 * @ViewsQuery(
 *   id = "az_publication_entity_query",
 *   title = @Translation("Entity Query"),
 *   help = @Translation("Query will be generated and run using the Drupal Entity Query API.")
 * )
 */
class ConfigEntityQuery extends Sql {

  /**
   * Commands variable.
   *
   * @var array
   */
  protected $commands = [];

  /**
   * EntityConditionGroups variable.
   *
   * @var [type]
   */
  protected $entityConditionGroups;

  /**
   * Undocumented variable.
   *
   * @var sorting
   */
  protected $sorting = [];

  /**
   * Undocumented variable.
   *
   * @var [type]
   */
  protected $groupOperator = NULL;

  /**
   * {@inheritdoc}
   */
  public function ensureTable($table, $relationship = NULL, JoinPluginBase $join = NULL) {}

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $alias = '', $params = []) {
    return $alias ? $alias : $field;
  }

  /**
   * Adds a condition.
   */
  public function condition($group, $field, $value = NULL, $operator = NULL, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'condition',
      'args' => [$field, $value, $operator, $langcode],
    ];
  }

  /**
   * Add's an exists command.
   */
  public function exists($group, $field, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'exists',
      'args' => [$field, $langcode],
    ];
  }

  /**
   * Add's an not exists command.
   */
  public function notExists($group, $field, $langcode = NULL) {
    $this->commands[$group][] = [
      'method' => 'notExists',
      'args' => [$field, $langcode],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    // Store the view in the object to be able to use it later.
    $this->view = $view;

    $view->initPager();
  }

  /**
   * {@inheritdoc}
   */
  public function addOrderBy($table, $field = NULL, $order = 'ASC', $alias = '', $params = []) {
    if ($alias) {
      $this->sorting[$alias] = $order;
    }
    elseif ($field) {
      $this->sorting[$field] = $order;
    }

  }

  /**
   * Executes query and fills the associated view object with according values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->pager['current_page'].
   *
   * $view->result should contain an array of objects. The array must use a
   * numeric index starting at 0.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view which is executed.
   */
  public function execute(ViewExecutable $view) {
    $this->groupOperator = $this->groupOperator ?? 'AND';
    $base_table = $this->view->storage->get('base_table');
    $data = \Drupal::service('views.views_data')->get($base_table);
    $entity_type = $data['table']['entity type'];
    $query = \Drupal::entityQuery($entity_type, $this->groupOperator);
    $this->entityConditionGroups = [
      $query,
    ];

    $this->buildConditions();
    $this->buildSorting($query);

    $ids = $query->execute();
    $results = \Drupal::entityTypeManager()->getStorage($entity_type)->loadMultiple($ids);
    $index = 0;
    /** @var \Drupal\Core\Config\Entity\ConfigEntityBase $result */
    foreach ($results as $result) {
      // @todo toArray() doesn't return all properties.
      $entity = $result->toArray();
      $entity['type'] = $entity_type;
      $entity['entity'] = $result;
      // 'index' key is required.
      $entity['index'] = $index++;
      $view->result[] = new ResultRow($entity);
    }
    $view->total_rows = count($view->result);
    $view->execute_time = 0;
  }

  /**
   * Build conditions based on it's groups.
   */
  protected function buildConditions() {
    foreach ($this->commands as $group => $grouped_commands) {
      $conditionGroup = $this->getConditionGroup($group);
      foreach ($grouped_commands as $command) {
        // call_user_func_array([$conditionGroup, $command['method']],
        // $command['args']);.
        switch ($command['method']) {
          case 'condition':
            $conditionGroup->condition(...$command['args']);
            break;

          case 'exists':
            $conditionGroup->exists(...$command['args']);
            break;

          case 'notExists':
            $conditionGroup->notExists(...$command['args']);
            break;

          // Add other cases as needed.
        }
      }
    }
  }

  /**
   * Returns a condition group.
   */
  protected function getConditionGroup($group) {
    if (!isset($this->entityConditionGroups[$group])) {
      $query = $this->entityConditionGroups[0];
      $condition = isset($this->where[$group]) && $this->where[$group]['type'] === 'OR' ? $query->orConditionGroup() : $query->andConditionGroup();
      $query->condition($condition);
      $this->entityConditionGroups[$group] = $condition;
    }
    return $this->entityConditionGroups[$group];
  }

  /**
   * Adds sorting to query.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The query to get configs.
   */
  protected function buildSorting(QueryInterface $query) {
    foreach ($this->sorting as $field => $direction) {
      $query->sort($field, $direction);
    }
  }

}
