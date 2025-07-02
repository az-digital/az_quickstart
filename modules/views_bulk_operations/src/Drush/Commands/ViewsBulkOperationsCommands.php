<?php

namespace Drupal\views_bulk_operations\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserStorageInterface;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionCompletedTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager;
use Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Defines Drush commands for the module.
 */
final class ViewsBulkOperationsCommands extends DrushCommands {

  use ViewsBulkOperationsActionCompletedTrait {
    message as traitMessage;
  }
  use AutowireTrait;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * ViewsBulkOperationsCommands object constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\views_bulk_operations\Service\ViewsbulkOperationsViewDataInterface $viewData
   *   VBO View data service.
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsActionManager $actionManager
   *   VBO Action manager service.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected  EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'views_bulk_operations.data')]
    protected ViewsbulkOperationsViewDataInterface $viewData,
    #[Autowire(service: 'plugin.manager.views_bulk_operations_action')]
    protected ViewsBulkOperationsActionManager $actionManager
  ) {
    parent::__construct();
    $this->userStorage = $entityTypeManager->getStorage('user');
  }

  /**
   * Execute an action on all results of the specified view.
   *
   * Use the --verbose option to see progress messages.
   */
  #[CLI\Command(name: 'views:bulk-operations:execute', aliases: ['vbo-execute', 'vbo-exec', 'views-bulk-operations:execute'])]
  #[CLI\Argument(name: 'view_id', description: 'The ID of the view to use.')]
  #[CLI\Argument(name: 'action_id', description: 'The ID of the action to execute.')]
  #[CLI\Option(name: 'display-id', description: 'ID of the display to use.')]
  #[CLI\Option(name: 'args', description: 'View arguments (slash is a delimiter).')]
  #[CLI\Option(name: 'exposed', description: 'Exposed filters (query string format).')]
  #[CLI\Option(name: 'batch-size', description: 'Processing batch size.')]
  #[CLI\Option(name: 'configuration', description: 'Action configuration (query string format).')]
  #[CLI\Option(name: 'user-id', description: 'The ID of the user account used for performing the operation.')]
  #[CLI\Usage(name: 'drush views:bulk-operations:execute some_view some_action', description: 'Execute some action on some view.')]
  #[CLI\Usage(name: 'drush vbo-execute some_view some_action --args=arg1/arg2 --batch-size=50', description: 'Execute some action on some view with arg1 and arg2 as the view arguments and 50 entities processed per batch.')]
  #[CLI\Usage(name: 'drush vbo-exec some_view some_action --configuration=&quot;key1=value1&amp;key2=value2&quot;', description: 'Execute some action on some view with the specified action configuration.')]
  public function vboExecute(
    $view_id,
    $action_id,
    array $options = [
      'display-id' => 'default',
      'args' => self::REQ,
      'exposed' => self::REQ,
      'batch-size' => 10,
      'configuration' => self::REQ,
      'user-id' => 1,
    ]
  ): void {
    if (empty($view_id) || empty($action_id)) {
      throw new \Exception('You must specify the view ID and the action ID parameters.');
    }

    $this->timer($options['verbose']);

    // Prepare options.
    if ($options['args']) {
      $options['args'] = \explode('/', $options['args']);
    }
    else {
      $options['args'] = [];
    }

    // Decode query string format options.
    foreach (['configuration', 'exposed'] as $name) {
      if (!empty($options[$name]) && !\is_array($options[$name])) {
        \parse_str($options[$name], $options[$name]);
      }
      else {
        $options[$name] = [];
      }
    }

    $vbo_data = [
      'list' => [],
      'view_id' => $view_id,
      'display_id' => $options['display-id'],
      'action_id' => $action_id,
      'preconfiguration' => $options['configuration'],
      'batch' => TRUE,
      'arguments' => $options['args'],
      'exposed_input' => $options['exposed'],
      'batch_size' => $options['batch-size'],
      'relationship_id' => 'none',
      // We set the clear_on_exposed parameter to true, otherwise with empty
      // selection exposed filters are not taken into account.
      'clear_on_exposed' => TRUE,
      'exclude_mode' => FALSE,
    ];

    // Login as the provided user, as drush 9+ doesn't support the
    // --user parameter. Default: user 1.
    $account = $this->userStorage->load($options['user-id']);
    $this->currentUser->setAccount($account);

    // Initialize the view to check if parameters are correct.
    if (!$view = Views::getView($vbo_data['view_id'])) {
      throw new \Exception('Incorrect view ID provided.');
    }
    if (!$view->setDisplay($vbo_data['display_id'])) {
      throw new \Exception('Incorrect view display ID provided.');
    }
    if (!empty($vbo_data['arguments'])) {
      $view->setArguments($vbo_data['arguments']);
    }
    if (!empty($vbo_data['exposed_input'])) {
      $view->setExposedInput($vbo_data['exposed_input']);
    }

    // We need total rows count for proper progress message display.
    $view->get_total_rows = TRUE;
    $view->execute();

    // Get relationship ID if VBO field exists.
    $vbo_data['relationship_id'] = 'none';
    foreach ($view->field as $field) {
      if ($field->options['id'] === 'views_bulk_operations_bulk_form') {
        $vbo_data['relationship_id'] = $field->options['relationship'];
      }
    }

    // Get total rows count.
    $this->viewData->init($view, $view->getDisplay(), $vbo_data['relationship_id']);
    $vbo_data['total_results'] = $this->viewData->getTotalResults($vbo_data['clear_on_exposed']);

    // Get action definition and check if action ID is correct.
    $action_definition = $this->actionManager->getDefinition($action_id);
    $vbo_data['action_label'] = (string) $action_definition['label'];

    $this->timer($options['verbose'], 'init');

    // Populate entity list.
    $context = [];
    do {
      $context['finished'] = 1;
      $context['message'] = '';
      ViewsBulkOperationsBatch::getList($vbo_data, $context);
      if (!empty($context['message'])) {
        $this->logger->info($context['message']);
      }
    } while ($context['finished'] < 1);
    $vbo_data = $context['results'];

    $this->timer($options['verbose'], 'list');

    // Execute the selected action.
    $context = [];
    do {
      $context['finished'] = 1;
      $context['message'] = '';
      ViewsBulkOperationsBatch::operation($vbo_data, $context);
      if (!empty($context['message'])) {
        $this->logger->info($context['message']);
      }
    } while ($context['finished'] < 1);

    // Display debug information.
    if ($options['verbose']) {
      $this->timer($options['verbose'], 'execute');
      $this->logger->info($this->t('Initialization time: @time ms.', [
        '@time' => $this->timer($options['verbose'], 'init'),
      ]));
      $this->logger->info($this->t('Entity list generation time: @time ms.', [
        '@time' => $this->timer($options['verbose'], 'list'),
      ]));
      $this->logger->info($this->t('Execution time: @time ms.', [
        '@time' => $this->timer($options['verbose'], 'execute'),
      ]));
    }

    static::finished(TRUE, $context['results'], []);
  }

  /**
   * List available actions for a view.
   */
  #[CLI\Command(name: 'views:bulk-operations:list', aliases: ['vbo-list'])]
  #[CLI\DefaultTableFields(fields: ['id', 'label', 'entity_type_id'])]
  #[CLI\FieldLabels(labels: ['id' => 'ID', 'label' => 'Label', 'entity_type_id' => 'Entity type ID'])]
  #[CLI\Usage(name: 'drush views:bulk-operations:list some_view some_action', description: 'Execute some action on some view.')]
  #[CLI\Usage(name: ' drush vbo-list', description: 'List all available actions info.')]
  public function vboList($options = ['format' => 'table']): RowsOfFields {
    $rows = [];
    $actions = $this->actionManager->getDefinitions(['nocache' => TRUE]);
    foreach ($actions as $id => $definition) {
      $rows[] = [
        'id' => $id,
        'label' => $definition['label'],
        'entity_type_id' => $definition['type'] ?: \dt('(any)'),
      ];
    }

    return new RowsOfFields($rows);
  }

  /**
   * Helper function to set / get timer.
   *
   * @param bool $debug
   *   Should the function do anything at all?
   * @param string $id
   *   ID of a specific timer span.
   *
   * @return mixed
   *   NULL or value of a specific timer if set.
   */
  protected function timer($debug = TRUE, $id = NULL): ?float {
    if (!$debug) {
      return NULL;
    }

    static $timers = [];

    if (!isset($id)) {
      $timers['start'] = \microtime(TRUE);
    }
    else {
      if (isset($timers[$id])) {
        \end($timers);
        do {
          if (\key($timers) === $id) {
            return \round((\current($timers) - \prev($timers)) * 1000, 3);
          }
          else {
            $result = \prev($timers);
          }
        } while ($result);
      }
      else {
        $timers[$id] = \microtime(TRUE);
      }
    }

    return NULL;
  }

  /**
   * Translates a string using the dt function.
   *
   * @param string $message
   *   The message to translate.
   * @param array $arguments
   *   (optional) The translation arguments.
   *
   * @return string
   *   The translated message.
   */
  protected function t($message, array $arguments = []): string {
    return \dt($message, $arguments);
  }

  /**
   * Message method.
   *
   * Overrides the one from the trait and uses Drush logger.
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE): void {
    // Status type no longer exists, mapping required.
    if ($type === 'status') {
      $type = LogLevel::INFO;
    }
    Drush::logger()->log($type, $message, []);
  }

}
