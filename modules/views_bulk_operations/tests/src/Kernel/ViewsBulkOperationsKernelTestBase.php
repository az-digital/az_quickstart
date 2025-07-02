<?php

namespace Drupal\Tests\views_bulk_operations\Kernel;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\Entity\User;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionCompletedTrait;
use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsBatch;

/**
 * Base class for Views Bulk Operations kernel tests.
 */
abstract class ViewsBulkOperationsKernelTestBase extends KernelTestBase {

  use ViewsBulkOperationsActionCompletedTrait {
    message as traitMessage;
  }

  use NodeCreationTrait {
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  protected const VBO_DEFAULTS = [
    'list' => [],
    'display_id' => 'default',
    'preconfiguration' => [],
    'batch' => TRUE,
    'arguments' => [],
    'exposed_input' => [],
    'batch_size' => 10,
    'relationship_id' => 'none',
    'exclude_mode' => FALSE,
    'clear_on_exposed' => FALSE,
  ];

  /**
   * Test node types already created.
   */
  protected ?array $testNodesTypes;


  /**
   * Test nodes data including titles and languages.
   */
  protected ?array $testNodesData;

  /**
   * VBO views data service.
   */
  protected ?ViewsBulkOperationsViewDataInterface $vboDataService;

  /**
   * Time service.
   */
  protected TimeInterface $time;

  /**
   * Messages static for testing purposes.
   *
   * @var array
   *
   * @todo Move this to message() method static when PHP 7.4 is no longer
   * supported.
   */
  private static $messages = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'content_translation',
    'views_bulk_operations',
    'views_bulk_operations_test',
    'views',
    'filter',
    'language',
    'text',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    $user = User::create();
    $user->setPassword('password');
    $user->enforceIsNew();
    $user->setEmail('email');
    $user->setUsername('user_name');
    $user->save();
    \user_login_finalize($user);

    $this->installConfig([
      'system',
      'filter',
      'views_bulk_operations_test',
      'language',
    ]);

    // Get time and VBO view data services.
    $this->time = $this->container->get('datetime.time');
    $this->vboDataService = $this->container->get('views_bulk_operations.data');
  }

  /**
   * Create some test nodes.
   *
   * @param array $test_node_data
   *   Describes test node bundles and properties.
   *
   * @see Drupal\Tests\views_bulk_operations\Kernel\ViewsBulkOperationsDataServiceTest::setUp()
   */
  protected function createTestNodes(array $test_node_data): void {
    $this->testNodesData = [];
    foreach ($test_node_data as $type_name => $type_data) {
      $type = NodeType::create([
        'type' => $type_name,
        'name' => $type_name,
      ]);
      $type->save();

      $count_languages = isset($type_data['languages']) ? \count($type_data['languages']) : 0;
      if ($count_languages) {
        for ($i = 0; $i < $count_languages; $i++) {
          $language = ConfigurableLanguage::createFromLangcode($type_data['languages'][$i]);
          $language->save();
        }
        $this->container->get('content_translation.manager')->setEnabled('node', $type_name, TRUE);
      }

      // Create some test nodes.
      $time = $this->time->getRequestTime();
      if (!isset($type_data['count'])) {
        $type_data['count'] = 10;
      }
      for ($i = 0; $i < $type_data['count']; $i++) {
        $time -= $i;
        $title = 'Title ' . $i;
        $node = $this->drupalCreateNode([
          'type' => $type_name,
          'title' => $title,
          'sticky' => FALSE,
          'created' => $time,
          'changed' => $time,
        ]);
        $this->testNodesData[$node->id()]['en'] = $title;

        if ($count_languages) {
          // It doesn't really matter to which languages we translate
          // from the API point of view so some randomness should be fine.
          $langcode = $type_data['languages'][\rand(0, $count_languages - 1)];
          $title = 'Translated title ' . $langcode . ' ' . $i;
          $translation = $node->addTranslation($langcode, [
            'title' => $title,
          ]);
          $translation->save();
          $this->testNodesData[$node->id()][$langcode] = $title;
        }
      }
    }
  }

  /**
   * Initialize and return the view described by $vbo_data.
   *
   * @param array $vbo_data
   *   An array of data passed to VBO Processor service.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view object.
   */
  protected function initializeView(array $vbo_data): ViewExecutable {
    if (!$view = Views::getView($vbo_data['view_id'])) {
      throw new \Exception('Incorrect view ID provided.');
    }
    if (!$view->setDisplay($vbo_data['display_id'])) {
      throw new \Exception('Incorrect view display ID provided.');
    }
    $view->built = FALSE;
    $view->executed = FALSE;

    return $view;
  }

  /**
   * Get a random list of results bulk keys.
   *
   * @param array $vbo_data
   *   An array of data passed to VBO Processor service.
   * @param array $deltas
   *   Array of result rows deltas.
   *
   * @return array
   *   List of results to process.
   */
  protected function getResultsList(array $vbo_data, array $deltas): array {
    // Merge in defaults.
    $vbo_data += self::VBO_DEFAULTS;

    $view = $this->initializeView($vbo_data);
    if (!empty($vbo_data['arguments'])) {
      $view->setArguments($vbo_data['arguments']);
    }
    if (!empty($vbo_data['exposed_input'])) {
      $view->setExposedInput($vbo_data['exposed_input']);
    }

    $view->setItemsPerPage(0);
    $view->setCurrentPage(0);
    $view->execute();

    $this->vboDataService->init($view, $view->getDisplay(), $vbo_data['relationship_id']);

    $list = [];
    $base_field = $view->storage->get('base_field');
    foreach ($deltas as $delta) {
      $entity = $this->vboDataService->getEntity($view->result[$delta]);

      $list[] = [
        $view->result[$delta]->{$base_field},
        $entity->language()->getId(),
        $entity->getEntityTypeId(),
        $entity->id(),
      ];
    }

    $view->destroy();

    return $list;
  }

  /**
   * Execute an action on a specific view results.
   *
   * @param array $vbo_data
   *   An array of data passed to VBO Processor service.
   *
   * @return mixed[]
   *   Array of results.
   */
  protected function executeAction(array $vbo_data): array {

    // Merge in defaults.
    $vbo_data += self::VBO_DEFAULTS;

    $view = $this->initializeView($vbo_data);
    $view->get_total_rows = TRUE;

    $view->execute();

    // Get total rows count.
    $this->vboDataService->init($view, $view->getDisplay(), $vbo_data['relationship_id']);
    $vbo_data['total_results'] = $this->vboDataService->getTotalResults($vbo_data['clear_on_exposed']);

    // Get action definition and check if action ID is correct.
    $action_definition = $this->container->get('plugin.manager.views_bulk_operations_action')->getDefinition($vbo_data['action_id']);
    if (!isset($vbo_data['action_label'])) {
      $vbo_data['action_label'] = (string) $action_definition['label'];
    }

    // Account for exclude mode.
    if ($vbo_data['exclude_mode']) {
      $vbo_data['exclude_list'] = $vbo_data['list'];
      $vbo_data['list'] = [];
    }

    // Populate entity list if empty.
    if (empty($vbo_data['list'])) {
      $context = [];
      do {
        $context['finished'] = 1;
        $context['message'] = '';
        ViewsBulkOperationsBatch::getList($vbo_data, $context);
      } while ($context['finished'] < 1);
      $vbo_data = $context['results'];
    }

    $summary = [
      'messages' => [],
    ];

    // Execute the selected action.
    $context = [];
    do {
      $context['finished'] = 1;
      $context['message'] = '';
      ViewsBulkOperationsBatch::operation($vbo_data, $context);
      if (!empty($context['message'])) {
        $summary['messages'][] = (string) $context['message'];
      }
    } while ($context['finished'] < 1);

    // Add information to the summary array.
    self::finished(TRUE, $context['results'], []);
    $summary += [
      'operations' => $context['results']['operations'],
      'finished_output' => self::message(),
    ];

    return $summary;
  }

  /**
   * Override trait message() method so we can get the output.
   *
   * @return string|null
   *   The message set previously or NULL.
   */
  public static function message($message = NULL, $type = 'status', $repeat = TRUE) {
    if ($message === NULL) {
      return self::$messages;
    }
    self::$messages[] = [
      'message' => (string) $message,
      'type' => $type,
    ];
    return NULL;
  }

}
