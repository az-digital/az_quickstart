<?php

namespace Drupal\views_bulk_operations\Plugin\views\field;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Views Bulk Operations field plugin.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("views_bulk_operations_bulk_form")]
class ViewsBulkOperationsBulkForm extends FieldPluginBase implements CacheableDependencyInterface, ContainerFactoryPluginInterface {

  use RedirectDestinationTrait;
  use UncacheableFieldHandlerTrait;
  use ViewsBulkOperationsFormTrait;

  /**
   * An array of actions that can be executed.
   *
   * @var array
   */
  protected array $actions = [];

  /**
   * An array of bulk form options.
   *
   * @var array
   */
  protected array $bulkOptions;

  /**
   * Tempstore data.
   *
   * This gets passed to the next requests if needed
   * or used in the views form submit handler directly.
   *
   * @var array|null
   */
  protected ?array $tempStoreData = NULL;

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   Extended action manager object.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionProcessorInterface $actionProcessor
   *   Views Bulk Operations action processor.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   User private temporary storage factory.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly ViewsbulkOperationsViewDataInterface $viewData,
    protected readonly ViewsBulkOperationsActionManager $actionManager,
    protected readonly ViewsBulkOperationsActionProcessorInterface $actionProcessor,
    protected readonly PrivateTempStoreFactory $tempStoreFactory,
    protected readonly AccountInterface $currentUser,
    protected readonly RequestStack $requestStack,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('views_bulk_operations.data'),
      $container->get('plugin.manager.views_bulk_operations_action'),
      $container->get('views_bulk_operations.processor'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    // Don't initialize if view has been built from VBO action processor.
    if (!empty($this->view->views_bulk_operations_processor_built)) {
      return;
    }

    // Set this property to always have the total rows information.
    $this->view->get_total_rows = TRUE;

    // Initialize VBO View Data object.
    $this->viewData->init($view, $display, $this->options['relationship']);

    // Fetch actions.
    $this->actions = [];
    $entity_types = $this->viewData->getEntityTypeIds();

    // Get actions only if there are any entity types set for the view.
    if (!empty($entity_types)) {
      foreach ($this->actionManager->getDefinitions() as $id => $definition) {
        if (empty($definition['type']) || \in_array($definition['type'], $entity_types, TRUE)) {
          $this->actions[$id] = $definition;
        }
      }
    }

    // Force form_step setting to TRUE due to #2879310.
    $this->options['form_step'] = TRUE;
  }

  /**
   * Update tempstore data.
   *
   * This function must be called a bit later, when the view
   * query has been built. Also, no point doing this on the view
   * admin page.
   *
   * @param array|null $view_entity_data
   *   See ViewsBulkOperationsViewDataInterface::getViewEntityData().
   */
  protected function updateTempstoreData(?array $view_entity_data = NULL): void {
    // Initialize tempstore object and get data if available.
    $this->tempStoreData = $this->getTempstoreData($this->view->id(), $this->view->current_display);

    // Parameters subject to change (either by an admin or user action).
    $this->viewData->init($this->view, $this->displayHandler, $this->options['relationship']);
    $variable = [
      'batch' => $this->options['batch'],
      'batch_size' => $this->options['batch'] ? $this->options['batch_size'] : 0,
      'total_results' => $this->viewData->getTotalResults($this->options['clear_on_exposed']),
      'relationship_id' => $this->options['relationship'],
      'arguments' => $this->view->args,
      'exposed_input' => $this->getExposedInput(),
    ];

    // Add bulk form keys when the form is displayed.
    if ($view_entity_data !== NULL) {
      $variable['bulk_form_keys'] = [];
      foreach ($view_entity_data as $row_index => $item) {
        $variable['bulk_form_keys'][$row_index] = $item[0];
      }
    }

    // Set redirect URL taking destination into account.
    $request = $this->requestStack->getCurrentRequest();
    $destination = $request->query->get('destination');
    if ($destination) {
      $request->query->remove('destination');
      unset($variable['exposed_input']['destination']);
      if (\strpos($destination, '/') !== 0) {
        $destination = '/' . $destination;
      }
      $variable['redirect_url'] = Url::fromUserInput($destination, []);
    }
    else {
      $variable['redirect_url'] = Url::createFromRequest(clone $this->requestStack->getCurrentRequest());
    }

    // Set exposed filters values to be kept after action execution.
    $query = $variable['redirect_url']->getOption('query');
    if (!$query) {
      $query = [];
    }
    $query += $variable['exposed_input'];
    $variable['redirect_url']->setOption('query', $query);

    // Create tempstore data object if it doesn't exist.
    if (!\is_array($this->tempStoreData)) {
      $this->tempStoreData = [];

      // Add initial values.
      $this->tempStoreData += [
        'view_id' => $this->view->id(),
        'display_id' => $this->view->current_display,
        'list' => [],
        'exclude_mode' => FALSE,
      ];

      // Add variable parameters.
      $this->tempStoreData += $variable;

      $this->setTempstoreData($this->tempStoreData);
    }

    // Update some of the tempstore data parameters if required.
    else {
      $update = FALSE;

      // Delete list if view arguments and optionally exposed filters changed.
      // NOTE: this should be subject to a discussion, maybe tempstore
      // should be arguments - specific?
      $clear_triggers = ['arguments'];
      if ($this->options['clear_on_exposed']) {
        $clear_triggers[] = 'exposed_input';
      }

      foreach ($clear_triggers as $trigger) {
        if ($variable[$trigger] !== $this->tempStoreData[$trigger]) {
          $this->tempStoreData[$trigger] = $variable[$trigger];
          $this->tempStoreData['list'] = [];
          $this->tempStoreData['exclude_mode'] = FALSE;
        }
        unset($variable[$trigger]);
        $update = TRUE;
      }

      foreach ($variable as $param => $value) {
        if (!isset($this->tempStoreData[$param]) || $this->tempStoreData[$param] != $value) {
          $update = TRUE;
          $this->tempStoreData[$param] = $value;
        }
      }

      if ($update) {
        $this->setTempstoreData($this->tempStoreData);
      }
    }

  }

  /**
   * Gets exposed input values from the view.
   *
   * @param array $exposed_input
   *   Current values of exposed input.
   *
   * @return array
   *   Exposed input sorted by filter names.
   */
  protected function getExposedInput(array $exposed_input = []): array {
    if (empty($exposed_input)) {
      // To avoid unnecessary reset of selection, we apply default values.
      // We do that, because default values can be provided or not
      // in the request, and it doesn't change results.
      $exposed_input = $this->view->getExposedInput();

      // Remove ajax_page_state that leaks to exposed input if AJAX is
      // enabled on the view.
      unset($exposed_input['ajax_page_state']);
      foreach ($this->view->exposed_raw_input as $key => $value) {
        if (!array_key_exists($key, $exposed_input)) {
          $exposed_input[$key] = $value;
        }
      }
    }
    // Sort values to avoid problems when comparing old and current exposed
    // input.
    \ksort($exposed_input);
    foreach ($exposed_input as $name => $value) {
      if (\is_array($value) && !empty($value)) {
        $exposed_input[$name] = $this->getExposedInput($value);
      }
    }
    return $exposed_input;
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser(): AccountInterface {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    // @todo Consider making the bulk operation form cacheable. See
    //   https://www.drupal.org/node/2503009.
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // No query here.
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['batch'] = ['default' => TRUE];
    $options['batch_size'] = ['default' => 10];
    $options['form_step'] = ['default' => TRUE];
    $options['ajax_loader'] = ['default' => FALSE];
    $options['buttons'] = ['default' => FALSE];
    $options['clear_on_exposed'] = ['default' => TRUE];
    $options['action_title'] = ['default' => $this->t('Action')];
    $options['selected_actions'] = ['default' => []];
    $options['force_selection_info'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // If the view type is not supported, suppress form display.
    // Also display information note to the user.
    if (empty($this->actions)) {
      $form = [
        '#type' => 'item',
        '#title' => $this->t('NOTE'),
        '#markup' => $this->t('Views Bulk Operations will work only with normal entity views and contrib module views that are integrated. See \Drupal\views_bulk_operations\EventSubscriber\ViewsBulkOperationsEventSubscriber class for integration best practice.'),
        '#prefix' => '<div class="scroll">',
        '#suffix' => '</div>',
      ];
      parent::buildOptionsForm($form, $form_state);
      return;
    }

    $form['#attributes']['class'][] = 'views-bulk-operations-ui';
    $form['#attached']['library'][] = 'views_bulk_operations/adminUi';

    $form['batch'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Process in a batch operation'),
      '#default_value' => $this->options['batch'],
    ];

    $form['batch_size'] = [
      '#title' => $this->t('Batch size'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#description' => $this->t('Only applicable if results are processed in a batch operation.'),
      '#default_value' => $this->options['batch_size'],
    ];

    $form['form_step'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Configuration form on new page (configurable actions)'),
      '#default_value' => $this->options['form_step'],
      // Due to #2879310 this setting must always be at TRUE.
      '#access' => FALSE,
    ];

    $form['ajax_loader'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show ajax throbber.'),
      '#description' => $this->t('With this enabled, a throbber will be shown when an ajax petition from VBO is triggered.'),
      '#default_value' => $this->options['ajax_loader'],
    ];

    $form['buttons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display selectable actions as buttons.'),
      '#default_value' => $this->options['buttons'],
    ];

    $form['clear_on_exposed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Clear selection when exposed filters change.'),
      '#description' => $this->t('With this enabled, selection will be cleared every time exposed filters are changed, select all will select all rows with exposed filters applied and view total count will take exposed filters into account. When disabled, select all selects all results in the view with empty exposed filters and one can change exposed filters while selecting rows without the selection being lost.'),
      '#default_value' => $this->options['clear_on_exposed'],
    ];

    $form['force_selection_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show selection info'),
      '#default_value' => $this->options['force_selection_info'],
      '#description' => $this->t('Should the selection info fieldset be shown above the view even if there is only one page of results and full selection can be seen in the view itself?'),
    ];

    $form['action_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Action title'),
      '#default_value' => $this->options['action_title'],
      '#description' => $this->t('The title shown above the actions dropdown.'),
    ];

    $form['selected_actions'] = [
      '#tree' => TRUE,
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Selected actions'),
      '#attributes' => ['class' => ['vbo-actions-widget']],
    ];

    // Load values for display.
    $form_values = $form_state->getValue(['options', 'selected_actions']);
    if (\is_null($form_values)) {
      $config_data = $this->options['selected_actions'];
      $selected_actions_data = [];
      foreach ($config_data as $item) {
        $selected_actions_data[$item['action_id']] = $item;
      }
    }
    else {
      $selected_actions_data = $form_values;
    }

    $table = [
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Weight'),
        $this->t('Title'),
      ],
      '#attributes' => [
        'id' => 'my-module-table',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'draggable-weight',
        ],
      ],
    ];

    // Set weights on actions - selected ones will always be first.
    $weight = -1000;
    foreach ($selected_actions_data as $id => $item) {
      if (!array_key_exists($id, $this->actions)) {
        continue;
      }
      $this->actions[$id]['weight'] = $weight++;
    }
    uasort($this->actions, [SortArray::class, 'sortByWeightElement']);

    $delta = 0;
    foreach ($this->actions as $id => $action) {
      $table[$delta] = [
        'data' => [],
      ];
      $table[$delta]['#attributes']['class'] = ['draggable'];
      $table[$delta]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $action['weight'] ?? 0,
        '#attributes' => [
          'class' => [
            'draggable-weight',
          ],
        ],
      ];

      $table[$delta]['container'] = [
        '#type' => 'container',
      ];

      $table[$delta]['container']['action_id'] = [
        '#type' => 'value',
        '#value' => $id,
      ];
      $table[$delta]['container']['state'] = [
        '#type' => 'checkbox',
        '#title' => $action['label'],
        '#default_value' => empty($selected_actions_data[$id]) ? 0 : 1,
        '#attributes' => ['class' => ['vbo-action-state']],
      ];

      $table[$delta]['container']['preconfiguration'] = [
        '#type' => 'details',
        '#title' => $this->t('Preconfiguration for "@action"', [
          '@action' => $action['label'],
        ]),
        '#states' => [
          'visible' => [
            \sprintf('[name="options[selected_actions][table][%d][container][state]"]', $delta) => ['checked' => TRUE],
          ],
        ],
      ];

      // Default label and action processing message overrides.
      $table[$delta]['container']['preconfiguration']['label_override'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Override label'),
        '#description' => $this->t('Leave empty for the default label.'),
        '#default_value' => $selected_actions_data[$id]['preconfiguration']['label_override'] ?? '',
      ];
      $table[$delta]['container']['preconfiguration']['message_override'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Override processing message'),
        '#description' => $this->t('Use the "@count" placeholder for number of processed items. Leave empty for the default message.'),
        '#default_value' => $selected_actions_data[$id]['preconfiguration']['message_override'] ?? '',
      ];

      // Also allow to force a default confirmation step for actoins that don't
      // have it implemented.
      if (empty($action['confirm_form_route_name'])) {
        $table[$delta]['container']['preconfiguration']['add_confirmation'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Add confirmation step'),
          '#default_value' => $selected_actions_data[$id]['preconfiguration']['add_confirmation'] ?? FALSE,
        ];
        $table[$delta]['container']['preconfiguration']['confirm_help_text'] = [
          '#type' => 'textarea',
          '#rows' => 2,
          '#title' => $this->t('Confirmation step help text'),
          '#default_value' => $selected_actions_data[$id]['preconfiguration']['confirm_help_text'] ?? FALSE,
          '#description' => $this->t('Available placeholders: @placeholders.', [
            '@placeholders' => implode(', ', ['%action', '%count']),
          ]),
          '#states' => [
            'visible' => [
              ':input[name="options[selected_actions][table][' . $delta . '][container][preconfiguration][add_confirmation]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }

      // Load preconfiguration form if available.
      if (\method_exists($action['class'], 'buildPreConfigurationForm')) {
        if (!isset($selected_actions_data[$id]['preconfiguration'])) {
          $selected_actions_data[$id]['preconfiguration'] = [];
        }
        $actionObject = $this->actionManager->createInstance($id);

        // Set the view so the configuration form can access to it.
        if ($this->view instanceof ViewExecutable) {
          if ($this->view->inited !== TRUE) {
            $this->view->initHandlers();
          }
          $actionObject->setView($this->view);
        }
        $table[$delta]['container']['preconfiguration'] = $actionObject->buildPreConfigurationForm($table[$delta]['container']['preconfiguration'], $selected_actions_data[$id]['preconfiguration'], $form_state);
      }

      $delta++;
    }
    $form['selected_actions']['table'] = $table;

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    $selected_actions = &$form_state->getValue(['options', 'selected_actions']);
    if ($selected_actions === NULL) {
      return;
    }
    $selected_actions = $selected_actions['table'];
    $selected_actions = \array_filter($selected_actions, static fn ($action_data) => !empty($action_data['container']['state']));

    foreach ($selected_actions as &$item) {
      unset($item['weight']);
      $item = array_merge($item, $item['container']);
      unset($item['state']);
      unset($item['container']);
      if (empty($item['preconfiguration']['label_override'])) {
        unset($item['preconfiguration']['label_override']);
      }
      if (empty($item['preconfiguration'])) {
        unset($item['preconfiguration']);
      }
    }
    $selected_actions = array_values($selected_actions);
    parent::submitOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Add empty classes if there are no actions available.
    if (empty($this->getBulkOptions())) {
      $this->options['element_label_class'] .= 'empty';
      $this->options['element_class'] .= 'empty';
      $this->options['element_wrapper_class'] .= 'empty';
      $this->options['label'] = '';
    }
    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    elseif (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state): void {
    // Make sure we do not accidentally cache this form.
    // @todo Evaluate this again in https://www.drupal.org/node/2503009.
    $form['#cache']['max-age'] = 0;

    // Add VBO class to the form.
    $form['#attributes']['class'][] = 'vbo-view-form';

    // Add VBO front UI and tableselect libraries for table display style.
    if ($this->view->style_plugin instanceof Table) {
      $form['#attached']['library'][] = 'core/drupal.tableselect';
      $this->view->style_plugin->options['views_bulk_operations_enabled'] = TRUE;
    }
    $form['#attached']['library'][] = 'views_bulk_operations/frontUi';
    if ($this->options['ajax_loader']) {
      $form['#attached']['drupalSettings']['vbo']['ajax_loader'] = TRUE;
    }

    // Only add the bulk form options and buttons if there are results and
    // any actions are available. Remove the default actions build array
    // otherwise.
    $action_options = $this->getBulkOptions();
    if (empty($this->view->result) || empty($action_options)) {
      unset($form['actions']);
      return;
    }

    // Get bulk form keys and entity labels for all rows.
    $this->viewData->init($this->view, $this->displayHandler, $this->options['relationship']);
    $entity_data = $this->viewData->getViewEntityData();

    // Update and fetch tempstore data to be available from this point
    // as it's needed for proper functioning of further logic.
    // Update tempstore data with bulk form keys only when the form is
    // displayed, but not when the form is being built before submission
    // (data is subject to change - new entities added or deleted after
    // the form display). TODO: consider using $form_state->set() instead.
    if (empty($form_state->getUserInput()['op'])) {
      $this->updateTempstoreData($entity_data);
    }
    else {
      $this->updateTempstoreData();
    }

    $form[$this->options['id']]['#tree'] = TRUE;

    // Get pager data if available.
    if (!empty($this->view->pager) && \method_exists($this->view->pager, 'hasMoreRecords')) {
      $pagerData = [
        'current' => $this->view->pager->getCurrentPage(),
        'more' => $this->view->pager->hasMoreRecords(),
      ];
    }

    // Render checkboxes for all rows.
    $page_selected = [];
    foreach ($entity_data as $row_index => $entity_data_item) {
      [$bulk_form_key, $entity_label] = $entity_data_item;
      $checked = isset($this->tempStoreData['list'][$bulk_form_key]);
      if (!empty($this->tempStoreData['exclude_mode'])) {
        $checked = !$checked;
      }

      if ($checked) {
        $page_selected[] = $bulk_form_key;
      }
      $form[$this->options['id']][$row_index] = [
        '#type' => 'checkbox',
        '#title' => $entity_label,
        '#title_display' => 'invisible',
        '#default_value' => $checked,
        '#return_value' => $bulk_form_key,
        '#attributes' => ['class' => ['js-vbo-checkbox']],
      ];

      // We should use #value instead of #default_value to always apply
      // the plugin's own saved checkbox state (data being changed after form
      // submission results in wrong values applied by the FAPI),
      // however - automated tests fail if it's done this way.
      // We have to apply values conditionally for tests to pass.
      if (isset($element['#value']) && $element['#value'] != $checked) {
        $element['#value'] = $checked;
      }
    }

    // Ensure a consistent container for filters/operations
    // in the view header.
    $form['header'] = [
      '#type' => 'container',
      '#weight' => -100,
    ];

    // Build the bulk operations action widget for the header.
    // Allow themes to apply .container-inline on this separate container.
    $form['header'][$this->options['id']] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'vbo-action-form-wrapper',
      ],
    ];

    // Display actions buttons or selector.
    if ($this->options['buttons']) {
      unset($form['actions']['submit']);
      foreach ($action_options as $id => $label) {
        $form['actions'][$id] = [
          '#type' => 'submit',
          '#value' => $label,
          '#attributes' => [
            'data-vbo' => 'vbo-action',
          ],
        ];
      }
    }
    else {
      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = $this->t('Apply to selected items');
      $form['actions']['submit']['#attributes']['data-vbo'] = 'vbo-action';

      $form['header'][$this->options['id']]['action'] = [
        '#type' => 'select',
        '#title' => $this->options['action_title'],
        '#options' => ['' => $this->t('-- Select action --')] + $action_options,
      ];
    }

    // Add AJAX functionality if actions are configurable through this form.
    if (empty($this->options['form_step'])) {
      $form['header'][$this->options['id']]['action']['#ajax'] = [
        'callback' => [self::class, 'viewsFormAjax'],
        'wrapper' => 'vbo-action-configuration-wrapper',
      ];
      $form['header'][$this->options['id']]['configuration'] = [
        '#type' => 'container',
        '#attributes' => ['id' => 'vbo-action-configuration-wrapper'],
      ];

      $action_id = $form_state->getValue('action');
      if (!empty($action_id)) {
        $action = $this->actions[$action_id];
        if ($this->isActionConfigurable($action)) {
          $actionObject = $this->actionManager->createInstance($action_id);
          $form['header'][$this->options['id']]['configuration'] += $actionObject->buildConfigurationForm($form['header'][$this->options['id']]['configuration'], $form_state);
          $form['header'][$this->options['id']]['configuration']['#config_included'] = TRUE;
        }
      }
    }

    // Selection info: displayed if exposed filters are set and selection
    // is not cleared when they change or "select all" element display
    // conditions are met. Also displayed by default if VBO field has such
    // configuration set.
    if ($this->options['force_selection_info']) {
      $display_select_all = TRUE;
    }
    else {
      $display_select_all = (
        !$this->options['clear_on_exposed'] &&
        !empty($this->view->getExposedInput())
      ) ||
      (
        isset($pagerData) &&
        (
          $pagerData['more'] ||
          $pagerData['current'] > 0
        )
      );
    }

    if ($display_select_all) {
      $count = empty($this->tempStoreData['exclude_mode']) ? \count($this->tempStoreData['list']) : $this->tempStoreData['total_results'] - \count($this->tempStoreData['list']);
      $form['header'][$this->options['id']]['multipage'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->formatPlural($count,
          'Selected 1 item',
          'Selected @count items'
        ),
        '#attributes' => [
          // Add view_id and display_id to be available for
          // js multipage selector functionality.
          'data-view-id' => $this->tempStoreData['view_id'],
          'data-display-id' => $this->tempStoreData['display_id'],
          'class' => ['vbo-multipage-selector'],
        ],
      ];
      $form['#attached']['drupalSettings']['vbo_selected_count'][$this->tempStoreData['view_id']][$this->tempStoreData['display_id']] = $count;

      // Get selection info elements.
      $form['header'][$this->options['id']]['multipage']['list'] = $this->getMultipageList($this->tempStoreData);
      $form['header'][$this->options['id']]['multipage']['clear'] = [
        '#type' => 'submit',
        '#value' => $this->t('Clear selection'),
        '#submit' => [[$this, 'clearSelection']],
        '#limit_validation_errors' => [],
      ];
    }

    // Select all results checkbox. Always display on non-table displays.
    if ($display_select_all || !($this->view->style_plugin instanceof Table)) {
      $form['header'][$this->options['id']]['select_all'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Select / deselect all results (all pages, @count total)', [
          '@count' => $this->tempStoreData['total_results'],
        ]),
        '#attributes' => ['class' => ['vbo-select-all']],
        '#default_value' => !empty($this->tempStoreData['exclude_mode']),
      ];
    }

    // Duplicate the form actions into the action container in the header.
    $form['header'][$this->options['id']]['actions'] = $form['actions'];
  }

  /**
   * AJAX callback for the views form.
   *
   * Currently not used due to #2879310.
   *
   * @return mixed[]
   *   Form element.
   */
  public static function viewsFormAjax(array $form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    $plugin_id = $trigger['#array_parents'][1];
    return $form['header'][$plugin_id]['configuration'];
  }

  /**
   * Returns the available operations for this form.
   *
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions(): array {
    if (!isset($this->bulkOptions)) {
      $this->bulkOptions = [];
      foreach ($this->options['selected_actions'] as $key => $selected_action_data) {
        if (!isset($this->actions[$selected_action_data['action_id']])) {
          continue;
        }

        $definition = $this->actions[$selected_action_data['action_id']];

        // Override label if applicable.
        if (!empty($selected_action_data['preconfiguration']['label_override'])) {
          $this->bulkOptions[$key] = $selected_action_data['preconfiguration']['label_override'];
        }
        else {
          $this->bulkOptions[$key] = $definition['label'];
        }
      }
    }

    return $this->bulkOptions;
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state): void {
    if ($form_state->get('step') == 'views_form_views_form') {

      $action_config = $this->options['selected_actions'][$form_state->getValue('action')];

      $action = $this->actions[$action_config['action_id']];

      $this->tempStoreData['action_id'] = $action_config['action_id'];
      $this->tempStoreData['action_label'] = empty($action_config['preconfiguration']['label_override']) ? (string) $action['label'] : $action_config['preconfiguration']['label_override'];
      $this->tempStoreData['relationship_id'] = $this->options['relationship'];
      $this->tempStoreData['preconfiguration'] = $action_config['preconfiguration'] ?? [];
      $this->tempStoreData['clear_on_exposed'] = $this->options['clear_on_exposed'];
      $this->tempStoreData['confirm_route'] = $action['confirm_form_route_name'];
      if (empty($this->tempStoreData['confirm_route']) && !empty($action_config['preconfiguration']['add_confirmation'])) {
        $this->tempStoreData['confirm_route'] = 'views_bulk_operations.confirm';
      }

      $configurable = $this->isActionConfigurable($action);

      // Get configuration if using AJAX.
      if ($configurable && empty($this->options['form_step'])) {
        $actionObject = $this->actionManager->createInstance($action_id);
        if (\method_exists($actionObject, 'submitConfigurationForm')) {
          $actionObject->submitConfigurationForm($form, $form_state);
          $this->tempStoreData['configuration'] = $actionObject->getConfiguration();
        }
        else {
          $form_state->cleanValues();
          $this->tempStoreData['configuration'] = $form_state->getValues();
        }
      }

      // Update list data with the current page selection.
      $selected_keys = [];
      $input = $form_state->getUserInput();
      foreach ($input[$this->options['id']] as $bulk_form_key) {
        $selected_keys[$bulk_form_key] = $bulk_form_key;
      }
      $select_all = $form_state->getValue('select_all');

      foreach ($this->tempStoreData['bulk_form_keys'] as $bulk_form_key) {
        if (
          (isset($selected_keys[$bulk_form_key]) && !$select_all) ||
          (!isset($selected_keys[$bulk_form_key]) && $select_all)
        ) {
          $this->tempStoreData['list'][$bulk_form_key] = $this->getListItem($bulk_form_key);
        }
        else {
          unset($this->tempStoreData['list'][$bulk_form_key]);
        }
      }

      // Update exclude mode setting.
      $this->tempStoreData['exclude_mode'] = !empty($select_all);

      // Routing - determine redirect route.
      //
      // Set default redirection due to issue #2952498.
      // @todo remove the next line when core cause is eliminated.
      $redirect_route = 'views_bulk_operations.execute_batch';

      if ($this->options['form_step'] && $configurable) {
        $redirect_route = 'views_bulk_operations.execute_configurable';
      }
      elseif (!empty($this->tempStoreData['confirm_route'])) {
        $redirect_route = $this->tempStoreData['confirm_route'];
      }

      // Redirect if needed.
      if (!empty($redirect_route)) {
        $this->setTempstoreData($this->tempStoreData);

        $form_state->setRedirect($redirect_route, [
          'view_id' => $this->view->id(),
          'display_id' => $this->view->current_display,
        ]);
      }
      // Or process rows here and now.
      else {
        $this->deleteTempstoreData();
        $redirect_response = $this->actionProcessor->executeProcessing($this->tempStoreData, $this->view);
        $form_state->setRedirectUrl($redirect_response->getTargetUrl());
      }
    }
  }

  /**
   * Clear the form selection along with entire tempstore.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function clearSelection(array &$form, FormStateInterface $form_state): void {
    $this->deleteTempstoreData();
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    if ($this->options['buttons']) {
      $trigger = $form_state->getTriggeringElement();
      $action_delta = \end($trigger['#parents']);
      $form_state->setValue('action', $action_delta);
    }
    else {
      $action_delta = $form_state->getValue('action');
    }

    if ($action_delta === '') {
      $form_state->setErrorByName('action', $this->t('Please select an action to perform.'));
    }
    else {
      if (!isset($this->options['selected_actions'][$action_delta])) {
        $form_state->setErrorByName('action', $this->t('Form error occurred, please try again.'));
      }
      elseif (!isset($this->actions[$this->options['selected_actions'][$action_delta]['action_id']])) {
        $form_state->setErrorByName('action', $this->t('Form error occurred, Unavailable action selected.'));
      }
    }

    if (!$form_state->getValue('select_all')) {
      // Update tempstore data to make sure we have also
      // results selected in other requests and validate if
      // anything is selected.
      $this->tempStoreData = $this->getTempstoreData();
      $selected = \array_filter($form_state->getValue($this->options['id']) ?? []);
      if (empty($this->tempStoreData['list']) && empty($selected)) {
        $form_state->setErrorByName('', $this->t('No items selected.'));
      }
    }

    // Action config validation (if implemented).
    if (empty($this->options['form_step']) && !empty($form['header'][$this->options['id']]['configuration']['#config_included'])) {
      $action_id = $form_state->getValue('action');
      $action = $this->actions[$action_id];
      if (\method_exists($action['class'], 'validateConfigurationForm')) {
        $actionObject = $this->actionManager->createInstance($action_id);
        $actionObject->validateConfigurationForm($form['header'][$this->options['id']]['configuration'], $form_state);
      }
    }

    // Update bulk form key list if the form has errors, as data might have
    // changed before validation took place.
    if ($form_state->getErrors()) {
      $bulk_form_keys = [];
      foreach ($form[$this->options['id']] as $row_index => $element) {
        if (\is_numeric($row_index) && isset($element['#return_value'])) {
          $bulk_form_keys[$row_index] = $element['#return_value'];
        }
      }
      $this->updateTempstoreData($bulk_form_keys);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * Check if an action is configurable.
   */
  protected function isActionConfigurable($action): bool {
    return \in_array('Drupal\Core\Plugin\PluginFormInterface', \class_implements($action['class']), TRUE) || \method_exists($action['class'], 'buildConfigurationForm');
  }

  /**
   * {@inheritdoc}
   */
  public function validate() {
    $errors = parent::validate();
    if ($this->displayHandler->usesFields()) {
      foreach ($this->displayHandler->getHandlers('field') as $field_handler) {
        if ($field_handler instanceof BulkForm) {
          $errors[] = $this->t("VBO and Drupal core bulk operations fields cannot be used in the same view display together.");
          break;
        }
      }
    }
    return $errors;
  }

}
