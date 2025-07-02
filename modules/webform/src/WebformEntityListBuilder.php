<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\EntityStorage\WebformEntityStorageTrait;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class WebformEntityListBuilder extends ConfigEntityListBuilder {

  use WebformEntityStorageTrait;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Search category.
   *
   * @var string
   */
  protected $category;

  /**
   * Search state.
   *
   * @var string
   */
  protected $state;

  /**
   * Bulk operations.
   *
   * @var bool
   */
  protected $bulkOperations;

  /**
   * Associative array container total results for displayed webforms.
   *
   * @var array
   */
  protected $totalNumberOfResults = [];

  /**
   * The database object.
   *
   * @var object
   */
  protected $database;

  /**
   * The user storage object.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The result limit.
   *
   * @var int
   */
  protected $limit;

  /**
   * The role storage object.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var \Drupal\webform\WebformEntityListBuilder $instance */
    $instance = parent::createInstance($container, $entity_type);

    $instance->request = $container->get('request_stack')->getCurrentRequest();
    $instance->configFactory = $container->get('config.factory');
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->database = $container->get('database');

    $instance->initialize();
    return $instance;
  }

  /**
   * Initialize WebformEntityListBuilder object.
   */
  protected function initialize() {
    $query = $this->request->query;
    $config = $this->configFactory->get('webform.settings');

    $this->keys = ($query->has('search')) ? $query->get('search') : '';
    $this->category = ($query->has('category')) ? $query->get('category') : $config->get('form.filter_category');
    $this->state = ($query->has('state')) ? $query->get('state') : $config->get('form.filter_state');
    $this->bulkOperations = $config->get('settings.webform_bulk_form') ?: FALSE;
    $this->limit = $config->get('form.limit') ?: 50;

    $this->submissionStorage = $this->entityTypeManager->getStorage('webform_submission');
    $this->userStorage = $this->entityTypeManager->getStorage('user');
    $this->roleStorage = $this->entityTypeManager->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Handler autocomplete redirect.
    if ($this->keys && preg_match('#\(([^)]+)\)$#', $this->keys, $match)) {
      if ($webform = $this->getStorage()->load($match[1])) {
        return new RedirectResponse($webform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $build = [];

    // Filter form.
    $build['filter_form'] = $this->buildFilterForm();

    // Display info.
    $build['info'] = $this->buildInfo();

    // Table.
    $build += parent::render();
    $build['table']['#sticky'] = TRUE;
    $build['table']['#attributes']['class'][] = 'webform-forms';

    // Bulk operations.
    if ($this->bulkOperations && $this->currentUser->hasPermission('administer webform')) {
      $build['table'] = \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformEntityBulkForm', $build['table']);
    }

    // Attachments.
    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    $build['#attached']['library'][] = 'webform/webform.admin';

    return $build;
  }

  /**
   * Build the filter form.
   *
   * @return array
   *   A render array representing the filter form.
   */
  protected function buildFilterForm() {
    // Add the filter by key(word) and/or state.
    if ($this->currentUser->hasPermission('administer webform')) {
      $state_options = [
        (string) $this->t('Active') => [
          '' => $this->t('All [@total]', ['@total' => $this->getTotal(NULL, NULL)]),
          WebformInterface::STATUS_OPEN => $this->t('Open [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_OPEN)]),
          WebformInterface::STATUS_CLOSED => $this->t('Closed [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_CLOSED)]),
          WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_SCHEDULED)]),
        ],
        (string) $this->t('Inactive') => [
          WebformInterface::STATUS_ARCHIVED => $this->t('Archived [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_ARCHIVED)]),
        ],
      ];
    }
    else {
      $state_options = [
        (string) $this->t('Active') => [
          '' => $this->t('All'),
          WebformInterface::STATUS_OPEN => $this->t('Open'),
          WebformInterface::STATUS_CLOSED => $this->t('Closed'),
          WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
        ],
        (string) $this->t('Inactive') => [
          WebformInterface::STATUS_ARCHIVED => $this->t('Archived'),
        ],
      ];
    }
    return \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformEntityFilterForm', $this->keys, $this->category, $this->state, $state_options);
  }

  /**
   * Build information summary.
   *
   * @return array
   *   A render array representing the information summary.
   */
  protected function buildInfo() {
    // Display info.
    if ($this->currentUser->hasPermission('administer webform') && ($total = $this->getTotal($this->keys, $this->category, $this->state))) {
      return [
        '#markup' => $this->formatPlural($total, '@count webform', '@count webforms'),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = [
      'data' => $this->t('Title'),
      'specifier' => 'title',
      'field' => 'title',
      'sort' => 'asc',
    ];
    $header['description'] = [
      'data' => $this->t('Description'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'description',
      'field' => 'description',
    ];
    $header['categories'] = [
      'data' => $this->t('Categories'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'status',
      'field' => 'status',
    ];
    $header['owner'] = [
      'data' => $this->t('Author'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'uid',
      'field' => 'uid',
    ];
    $header['results'] = [
      'data' => $this->t('Results'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      'specifier' => 'results',
      'field' => 'results',
    ];
    $header['operations'] = [
      'data' => $this->t('Operations'),
    ];
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformInterface $entity */

    // Title.
    //
    // ISSUE: Webforms that the current user can't access are not being hidden via the EntityQuery.
    // WORK-AROUND: Don't link to the webform.
    // See: Access control is not applied to config entity queries
    // https://www.drupal.org/node/2636066
    $row['title']['data']['title'] = ['#markup' => ($entity->access('submission_page')) ? $entity->toLink()->toString() : $entity->label()];
    if ($entity->isTemplate()) {
      $row['title']['data']['template'] = ['#markup' => ' <b>(' . $this->t('Template') . ')</b>'];
    }

    // Description.
    $row['description']['data'] = WebformHtmlEditor::checkMarkup($entity->get('description'));

    // Categories.
    $row['categories']['data']['#markup'] = implode('; ', $entity->get('categories') ?: []);

    // Status.
    $t_args = ['@label' => $entity->label()];
    if ($entity->isArchived()) {
      $row['status']['data'] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#value' => $this->t('Archived'),
        '#attributes' => ['aria-label' => $this->t('@label is archived', $t_args)],
      ];
      $row['status'] = $this->t('Archived');
    }
    else {
      switch ($entity->get('status')) {
        case WebformInterface::STATUS_OPEN:
          $status = $this->t('Open');
          $aria_label = $this->t('@label is open', $t_args);
          break;

        case WebformInterface::STATUS_CLOSED:
          $status = $this->t('Closed');
          $aria_label = $this->t('@label is closed', $t_args);
          break;

        case WebformInterface::STATUS_SCHEDULED:
          $status = $this->t('Scheduled (@state)', ['@state' => $entity->isOpen() ? $this->t('Open') : $this->t('Closed')]);
          $aria_label = $this->t('@label is scheduled and is @state', $t_args + ['@state' => $entity->isOpen() ? $this->t('open') : $this->t('closed')]);
          break;

        default:
          return [];
      }

      if ($entity->access('update')) {
        $row['status']['data'] = $entity->toLink($status, 'settings-form', ['query' => $this->getDestinationArray()])->toRenderable() + [
          '#attributes' => ['aria-label' => $aria_label],
        ];
      }
      else {
        $row['status']['data'] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $status,
          '#attributes' => ['aria-label' => $aria_label],
        ];
      }
    }

    // Owner.
    $row['owner'] = ($owner = $entity->getOwner()) ? $owner->toLink() : '';

    // Results.
    $result_total = $this->totalNumberOfResults[$entity->id()];
    $results_disabled = $entity->isResultsDisabled();
    $results_access = $entity->access('submission_view_any');
    if ($results_disabled || !$results_access) {
      $row['results'] = ($result_total ? $result_total : '')
        . ($result_total && $results_disabled ? ' ' : '')
        . ($results_disabled ? $this->t('(Disabled)') : '');
    }
    else {
      $row['results'] = [
        'data' => [
          '#type' => 'link',
          '#title' => $result_total,
          '#attributes' => [
            'aria-label' => $this->formatPlural($result_total, '@count result for @label', '@count results for @label', ['@label' => $entity->label()]),
          ],
          '#url' => $entity->toUrl('results-submissions'),
        ],
      ];
    }

    // Operations.
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    /** @var \Drupal\webform\WebformInterface $entity */

    $operations = [];
    if ($entity->access('update')) {
      $operations['edit'] = [
        'title' => $this->t('Build'),
        'url' => $this->ensureDestination($entity->toUrl('edit-form')),
        'weight' => 0,
      ];
    }
    if ($entity->access('submission_page')) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'url' => $entity->toUrl('canonical'),
        'weight' => 10,
      ];
    }
    if ($entity->access('test')) {
      $operations['test'] = [
        'title' => $this->t('Test'),
        'url' => $entity->toUrl('test-form'),
        'weight' => 20,
      ];
    }
    if ($entity->access('submission_view_any') && !$entity->isResultsDisabled()) {
      $operations['results'] = [
        'title' => $this->t('Results'),
        'url' => $entity->toUrl('results-submissions'),
        'weight' => 30,
      ];
    }
    if ($entity->access('update')) {
      $operations['settings'] = [
        'title' => $this->t('Settings'),
        'url' => $entity->toUrl('settings'),
        'weight' => 40,
      ];
    }
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'url' => $entity->toUrl('duplicate-form'),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        'weight' => 90,
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'url' => $this->ensureDestination($entity->toUrl('delete-form')),
        'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        'weight' => 100,
      ];
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $header = $this->buildHeader();
    if ($this->request->query->get('order') === (string) $header['results']['data']) {
      $entity_ids = $this->getQuery($this->keys, $this->category, $this->state)
        ->execute();

      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $totals = array_map('intval', $query->execute()->fetchAllKeyed());
        foreach ($totals as $entity_id => $total) {
          $this->totalNumberOfResults[$entity_id] = $total;
        }
      }

      // Sort totals.
      asort($this->totalNumberOfResults, SORT_NUMERIC);
      if ($this->request->query->get('sort') === 'desc') {
        $this->totalNumberOfResults = array_reverse($this->totalNumberOfResults, TRUE);
      }

      // Build an associative array of entity ids from totals.
      $entity_ids = array_keys($this->totalNumberOfResults);
      $entity_ids = array_combine($entity_ids, $entity_ids);

      // Manually initialize and apply paging to the entity ids.
      $page = $this->request->query->get('page') ?: 0;
      $total = count($entity_ids);
      $limit = $this->getLimit();
      $start = ($page * $limit);
      \Drupal::service('pager.manager')->createPager($total, $limit);
      return array_slice($entity_ids, $start, $limit, TRUE);
    }
    else {
      $query = $this->getQuery($this->keys, $this->category, $this->state);
      $query->tableSort($header);
      $query->pager($this->getLimit());
      $entity_ids = $query->execute();

      // Calculate totals.
      // @see \Drupal\webform\WebformEntityStorage::getTotalNumberOfResults
      if ($entity_ids) {
        $query = $this->database->select('webform_submission', 'ws');
        $query->fields('ws', ['webform_id']);
        $query->condition('webform_id', $entity_ids, 'IN');
        $query->addExpression('COUNT(sid)', 'results');
        $query->groupBy('webform_id');
        $this->totalNumberOfResults = array_map('intval', $query->execute()->fetchAllKeyed());
      }

      // Make sure all entity ids have totals.
      $this->totalNumberOfResults += array_fill_keys($entity_ids, 0);

      return $entity_ids;
    }
  }

  /**
   * Get the total number of submissions.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   * @param string $state
   *   (optional) Webform state. Can be 'open' or 'closed'.
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal($keys = '', $category = '', $state = '') {
    return $this->getQuery($keys, $category, $state)->accessCheck(FALSE)
      ->count()
      ->execute();
  }

  /**
   * Get the base entity query filtered by webform and search.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   * @param string $state
   *   (optional) Webform state. Can be 'open' or 'closed'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $category = '', $state = ''): QueryInterface {
    $query = $this->getStorage()->getQuery();

    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('id', $keys, 'CONTAINS')
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');

      // Users and roles we need to scan all webforms.
      $access_value = NULL;
      if ($accounts = $this->getEntityStorage('user')->loadByProperties(['name' => $keys])) {
        $account = reset($accounts);
        $access_type = 'users';
        $access_value = $account->id();
      }
      elseif ($role = $this->getEntityStorage('user_role')->load($keys)) {
        $access_type = 'roles';
        $access_value = $role->id();
      }
      if ($access_value) {
        // Collect the webform ids that the user or role has access to.
        $webform_ids = [];
        /** @var \Drupal\webform\WebformInterface $webforms */
        $webforms = $this->getStorage()->loadMultiple();
        foreach ($webforms as $webform) {
          $access_rules = $webform->getAccessRules();
          foreach ($access_rules as $access_rule) {
            if (!empty($access_rule[$access_type]) && in_array($access_value, $access_rule[$access_type])) {
              $webform_ids[] = $webform->id();
              break;
            }
          }
        }
        if ($webform_ids) {
          $or->condition('id', $webform_ids, 'IN');
        }
        // Also check the webform's owner.
        if ($access_type === 'users') {
          $or->condition('uid', $access_value);
        }
      }
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      // Collect the webform ids that use the selected category.
      $webform_ids = [];
      /** @var \Drupal\webform\WebformInterface $webforms */
      $webforms = $this->getStorage()->loadMultiple();
      foreach ($webforms as $webform) {
        if (in_array($category, (array) $webform->get('categories'))) {
          $webform_ids[] = $webform->id();
        }
      }
      $query->condition('id', $webform_ids, 'IN');
    }

    // Filter by (form) state.
    switch ($state) {
      case WebformInterface::STATUS_OPEN;
      case WebformInterface::STATUS_CLOSED;
      case WebformInterface::STATUS_SCHEDULED;
        $query->condition('status', $state);
        break;
    }

    // Always filter by archived state.
    $query->condition('archive', $state === WebformInterface::STATUS_ARCHIVED ? 1 : 0);

    // Filter out templates if the webform_template.module is enabled.
    if ($this->moduleHandler()->moduleExists('webform_templates') && $state !== WebformInterface::STATUS_ARCHIVED) {
      $query->condition('template', FALSE);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    /** @var \Drupal\webform\WebformInterface[] $entities */
    $entities = $this->storage->loadMultiple($entity_ids);

    // If the user is not a webform admin, check access to each webform.
    if (!$this->isAdmin()) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('update', $this->currentUser)
          && !$entity->access('submission_view_any', $this->currentUser)) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;
  }

  /**
   * Get number of entities to list per page.
   *
   * @return int|false
   *   The number of entities to list per page, or FALSE to list all entities.
   */
  protected function getLimit() {
    return ($this->isAdmin()) ? $this->limit : FALSE;
  }

  /**
   * Is the current user a webform administrator.
   *
   * @return bool
   *   TRUE if the current user has 'administer webform' or 'edit any webform'
   *   permission.
   */
  protected function isAdmin() {
    $account = $this->currentUser;
    return ($account->hasPermission('administer webform') || $account->hasPermission('edit any webform') || $account->hasPermission('view any webform submission'));
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureDestination(Url $url) {
    // Never add a destination to operation URLs.
    return $url;
  }

}
