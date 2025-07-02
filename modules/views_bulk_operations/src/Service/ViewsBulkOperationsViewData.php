<?php

namespace Drupal\views_bulk_operations\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\ViewEntityDataEvent;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Gets Views data needed by VBO.
 */
class ViewsBulkOperationsViewData implements ViewsBulkOperationsViewDataInterface {

  use ViewsBulkOperationsFormTrait;

  /**
   * The current view.
   */
  protected ?ViewExecutable $view = NULL;

  /**
   * The display handler.
   */
  protected DisplayPluginBase $displayHandler;

  /**
   * The relationship ID.
   */
  protected string $relationship;

  /**
   * Views data concerning the current view.
   *
   * @var array
   */
  protected array $data = [];

  /**
   * Entity type ids returned by this view.
   *
   * @var array
   */
  protected array $entityTypeIds;

  /**
   * Entity getter data.
   *
   * @var array
   */
  protected array $entityGetter;

  /**
   * Object constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher service.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   Pager manager service.
   */
  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly PagerManagerInterface $pagerManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, $relationship): void {
    // Don't reinitialize if we're working on the same view.
    if (
      $this->view !== NULL &&
      $this->view->id() === $view->id() &&
      $this->view->current_display === $view->current_display
    ) {
      return;
    }

    $this->view = $view;
    $this->displayHandler = $display;
    $this->relationship = $relationship;

    // Get view entity types and results fetcher callable.
    $event = new ViewsBulkOperationsEvent($this->getViewProvider(), $this->getData(), $view);

    $this->eventDispatcher->dispatch($event, ViewsBulkOperationsEvent::NAME);

    $this->entityTypeIds = $event->getEntityTypeIds();
    $this->entityGetter = $event->getEntityGetter();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeIds(): array {
    return $this->entityTypeIds;
  }

  /**
   * Helper function to get data of the current view.
   *
   * @return array
   *   Part of views data that refers to the current view.
   */
  protected function getData(): array {
    $viewsData = Views::viewsData();

    if (!empty($this->relationship) && $this->relationship != 'none') {
      $relationship = $this->displayHandler->getOption('relationships')[$this->relationship];
      $table_data = $viewsData->get($relationship['table']);
      $key = $table_data[$relationship['field']]['relationship']['base'];
    }
    else {
      $key = $this->view->storage->get('base_table');
    }

    if (!\array_key_exists($key, $this->data)) {
      $this->data[$key] = $viewsData->get($key);
    }

    return $this->data[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getViewProvider(): string {
    $views_data = $this->getData();
    if (isset($views_data['table']['provider'])) {
      return $views_data['table']['provider'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getViewBaseField(): string {
    $views_data = $this->getData();
    if (isset($views_data['table']['base']['field'])) {
      return $views_data['table']['base']['field'];
    }
    throw new \Exception('Unable to get base field for the view.');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(ResultRow $row): ?EntityInterface {
    if (!empty($this->entityGetter['file'])) {
      require_once $this->entityGetter['file'];
    }
    if (\is_callable($this->entityGetter['callable'])) {
      return \call_user_func($this->entityGetter['callable'], $row, $this->relationship, $this->view);
    }
    else {
      if (\is_array($this->entityGetter['callable'])) {
        if (\is_object($this->entityGetter['callable'][0])) {
          $info = \get_class($this->entityGetter['callable'][0]);
        }
        else {
          $info = $this->entityGetter['callable'][0];
        }
        $info .= '::' . $this->entityGetter['callable'][1];
      }
      else {
        $info = $this->entityGetter['callable'];
      }
      throw new \Exception(\sprintf("Entity getter method %s doesn't exist.", $info));
    }
  }

  /**
   * Get the total count of results on all pages.
   *
   * @param bool $clear_on_exposed
   *   Are we clearing selection on exposed filters change?
   *
   * @return int
   *   The total number of results this view displays.
   */
  public function getTotalResults($clear_on_exposed = FALSE): ?int {
    $total_results = NULL;

    if (!$clear_on_exposed && !empty($this->view->getExposedInput())) {
      if ($pager = $this->view->getPager()) {
        $pager_options = $pager->options;
        $pager_options['total_items'] = $pager->getTotalItems();
      }

      // Execute the view without exposed input set.
      $view = Views::getView($this->view->id());
      $view->setDisplay($this->view->current_display);
      // If there are any arguments, pass them through.
      if (!empty($this->view->args)) {
        $view->setArguments($this->view->args);
      }
      $view->get_total_rows = TRUE;

      // We have to set exposed input to some value here, empty
      // value will be overwritten with query params by Views so
      // setting an empty array wouldn't work.
      $view->setExposedInput(['_views_bulk_operations_override' => TRUE]);
    }
    else {
      $view = $this->view;
    }

    // Execute the view if not already executed.
    $view->execute();

    if (!empty($view->pager->total_items)) {
      $total_results = $view->pager->total_items;
    }
    elseif (!empty($view->total_rows)) {
      $total_results = $view->total_rows;
    }

    if (!empty($pager_options) && isset($pager_options['id'])) {
      $this->pagerManager->createPager($pager_options['total_items'], $pager_options['items_per_page'], $pager_options['id']);
    }

    return $total_results;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityDefault(ResultRow $row, $relationship_id, ViewExecutable $view): ?FieldableEntityInterface {
    if ($relationship_id == 'none') {
      if (!empty($row->_entity)) {
        $entity = $row->_entity;
      }
    }
    elseif (isset($row->_relationship_entities[$relationship_id])) {
      $entity = $row->_relationship_entities[$relationship_id];
    }
    else {
      throw new \Exception('Unexpected view result row structure.');
    }

    if (!$entity instanceof EntityInterface) {
      return NULL;
    }

    if ($entity instanceof TranslatableInterface && $entity->isTranslatable()) {

      // Try to find a field alias for the langcode.
      // Assumption: translatable entities always
      // have a langcode key.
      $language_field = '';
      $langcode_key = $entity->getEntityType()->getKey('langcode');
      $base_table = $view->storage->get('base_table');
      foreach ($view->query->fields as $field) {
        if (
          $field['field'] === $langcode_key && (
            empty($field['base_table']) ||
            $field['base_table'] === $base_table
          )
        ) {
          $language_field = $field['alias'];
          break;
        }
      }
      if (!$language_field) {
        $language_field = $langcode_key;
      }

      if (isset($row->{$language_field})) {
        return $entity->getTranslation($row->{$language_field});
      }
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getViewEntityData(): array {
    // Allow other modules to get bulk form keys and entity labels for
    // possible performance improvements on non-standard views.
    $event = new ViewEntityDataEvent($this->getViewProvider(), $this->getData(), $this->view);
    $this->eventDispatcher->dispatch($event, ViewEntityDataEvent::NAME);
    $view_entity_data = $event->getViewEntityData();
    if (count($view_entity_data) !== 0) {
      return $view_entity_data;
    }

    // If no data has been provided, get it the default way.
    $base_field = $this->view->storage->get('base_field');
    foreach ($this->view->result as $row_index => $row) {
      if ($entity = $this->getEntity($row)) {
        $view_entity_data[$row_index] = [
          self::calculateEntityBulkFormKey(
            $entity,
            $row->{$base_field}
          ),
          $entity->label(),
        ];
      }
    }

    return $view_entity_data;
  }

}
