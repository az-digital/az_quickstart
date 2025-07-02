<?php

namespace Drupal\config_inspector\Controller;

use Drupal\Component\Serialization\Yaml;
use Drupal\config_inspector\ConfigInspectorManager;
use Drupal\config_inspector\ConfigSchemaValidatability;
use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\TypedData\TypedDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines a controller for the config_inspector module.
 */
class ConfigInspectorController extends ControllerBase {

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * The configuration inspector manager.
   *
   * @var \Drupal\config_inspector\ConfigInspectorManager
   */
  protected $configInspectorManager;

  /**
   * The string translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The dumper.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface
   */
  protected $dumper;

  /**
   * {@inheritdoc}
   */
  public function __construct(StorageInterface $storage, ConfigInspectorManager $config_inspector_manager, TranslationManager $translation_manager, FormBuilderInterface $form_builder, ModuleHandlerInterface $module_handler) {
    $this->configStorage = $storage;
    $this->configInspectorManager = $config_inspector_manager;
    $this->translationManager = $translation_manager;
    $this->formBuilder = $form_builder;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('config.storage'),
      $container->get('config_inspector.manager'),
      $container->get('string_translation'),
      $container->get('form_builder'),
      $container->get('module_handler')
    );
    if ($container->has('devel.dumper')) {
      $form->dumper = $container->get('devel.dumper');
    }
    return $form;
  }

  /**
   * Builds a page listing all configuration keys to inspect.
   *
   * @return array
   *   A render array representing the list.
   */
  public function overview() {
    $page['#attached']['library'][] = 'system/drupal.debounce';
    $page['#attached']['library'][] = 'config_inspector/config_inspector';

    $page['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $page['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#size' => 30,
      '#placeholder' => $this->t('Search for a configuration key'),
      '#attributes' => [
        'id' => 'schema-filter-text',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the configuration key to filter by.'),
      ],
    ];

    $page['filters']['schema_has_errors'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Only show errors'),
      '#label_attributes' => ['for' => 'schema-has-errors'],
      '#attributes' => [
        'id' => 'schema-has-errors',
      ],
    ];

    $page['table'] = [
      '#type' => 'table',
      '#caption' => $this->t('<h6>Legend for <q>Data</q>:</h6><dl><dt>✅❓</dt><dd>Correct primitive type, detailed validation impossible.</dd><dt>✅✅</dt><dd>Correct primitive type, passed all validation constraints.</dd></dl>'),
      '#sticky' => TRUE,
      '#header' => [
        'name' => $this->t('Configuration key'),
        'schema' => $this->t('Schema'),
        'validatability' => $this->t('Validatable'),
        'violations' => $this->t('Data'),
        'list' => $this->t('List'),
        'tree' => $this->t('Tree'),
        'form' => $this->t('Form'),
        'raw' => $this->t('Raw data'),
        'download' => $this->t('Download'),
      ],
      '#attributes' => [
        'class' => [
          'config-inspector-list',
        ],
      ],
    ];

    foreach ($this->configStorage->listAll() as $name) {
      $label = '<span class="table-filter-text-source">' . $name . '</span>';
      // Elements without a schema are displayed to help debugging.
      if (!$this->configInspectorManager->hasSchema($name)) {
        $page['table'][] = [
          'name' => ['#markup' => $label],
          'schema' => [
            '#markup' => $this->t('None'),
            '#wrapper_attributes' => [
              'data-has-errors' => TRUE,
            ],
          ],
          'list' => ['#markup' => $this->t('N/A')],
          'tree' => ['#markup' => $this->t('N/A')],
          'form' => ['#markup' => $this->t('N/A')],
          'raw' => Link::createFromRoute($this->t('Raw data'), 'config_inspector.raw_page', ['name' => $name])->toRenderable(),
          'download' => Link::createFromRoute($this->t('Download'), 'config_inspector.download', ['name' => $name])->toRenderable(),
        ];
      }
      else {
        $schema = $this->t('Correct');
        $result = $this->configInspectorManager->checkValues($name);
        $raw_violations = $this->configInspectorManager->validateValues($name);
        $raw_validatability = $this->configInspectorManager->checkValidatabilityValues($name);
        if (is_array($result)) {
          // The no-schema case is covered above already, if we got errors, the
          // schema is partial.
          $schema = $this->translationManager->formatPlural(count($result), '@count error', '@count errors');
        }
        $page['table'][] = [
          'name' => ['#markup' => $label],
          'schema' => [
            '#markup' => $schema,
            '#wrapper_attributes' => [
              'data-has-errors' => is_array($result),
            ],
          ],
          'validatability' => [
            '#markup' => $raw_validatability->isComplete()
              ? $this->t('Validatable')
              : $this->t('@validatability%', ['@validatability' => intval($raw_validatability->computePercentage() * 100)]),
          ],
          'violations' => [
            '#markup' => $raw_violations->count() === 0
              ? ($raw_validatability->isComplete()
                ? $this->t('<abbr title="Correct primitive type, passed all validation constraints.">✅✅</abbr>')
                : $this->t('<abbr title="Correct primitive type, detailed validation impossible.">✅❓</abbr>')
            )
              : $this->translationManager->formatPlural(
                $raw_violations->count(),
                '@count error',
                '@count errors',
            ),
            '#wrapper_attributes' => [
              'data-has-errors' => $raw_violations->count() > 0,
            ],
          ],
          'list' => [
            '#markup' => Link::createFromRoute($this->t('List'), 'config_inspector.list_page', ['name' => $name])->toString(),
          ],
          'tree' => [
            '#markup' => Link::createFromRoute($this->t('Tree'), 'config_inspector.tree_page', ['name' => $name])->toString(),
          ],
          'form' => [
            '#markup' => Link::createFromRoute($this->t('Form'), 'config_inspector.form_page', ['name' => $name])->toString(),
          ],
          'raw' => [
            '#markup' => Link::createFromRoute($this->t('Raw data'), 'config_inspector.raw_page', ['name' => $name])->toString(),
          ],
          'download'  => [
            '#markup' => Link::createFromRoute($this->t('Download'), 'config_inspector.download', ['name' => $name])->toString(),
          ],
        ];
      }
    }
    return $page;
  }

  /**
   * List (table) inspection view of the configuration.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a list view.
   */
  public function getList($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $output = $this->formatList($name, $config_schema);
    $output['#title'] = $this->t('List of configuration data for %name', ['%name' => $name]);
    return $output;
  }

  /**
   * Tree inspection view of the configuration.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a tree view.
   */
  public function getTree($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $validatability = $this->configInspectorManager->checkValidatabilityValues($name);
    $output = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'config-inspector-tree',
        ],
      ],
      '#attached' => [
        'library' => [
          'config_inspector/config_inspector',
        ],
      ],
    ];
    $output += $this->formatTree($name, $config_schema, $validatability);
    $output['#title'] = $this->t('Tree of configuration data for %name', ['%name' => $name]);
    return $output;
  }

  /**
   * Form based configuration data inspection.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a form view.
   */
  public function getForm($name) {
    $config_schema = $this->configInspectorManager->getConfigSchema($name);
    $output = $this->formBuilder->getForm('\Drupal\config_inspector\Form\ConfigInspectorItemForm', $config_schema);
    $output['#title'] = $this->t('Raw configuration data for %name', ['%name' => $name]);
    return $output;
  }

  /**
   * Raw configuration data inspection.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return array
   *   A render array for a raw dump view.
   */
  public function getRawData($name) {
    $data = $this->configInspectorManager->getConfigData($name);
    $output = [
      '#title' => $this->t('Raw configuration data for %name', ['%name' => $name]),
      'config' => $this->formatData($data, 'Configuration data'),
      'schema' => $this->formatData(NULL, 'Configuration schema'),
      'validation' => $this->formatData(TRUE, 'Configuration validation'),
    ];

    if ($this->configInspectorManager->hasSchema($name)) {
      $definition = $this->configInspectorManager->getDefinition($name);
      $output['schema'] = $this->formatData($definition, 'Configuration schema');

      $result = $this->configInspectorManager->checkValues($name);
      if (is_array($result)) {
        $output['validation'] = $this->formatData($result, 'Configuration validation');
      }
    }

    return $output;
  }

  /**
   * Download raw data.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return config as a response yaml file.
   */
  public function download($name) {
    $data = $this->configInspectorManager->getConfigData($name);
    $data = Yaml::encode($data);
    $headers = [
      'Content-disposition' => 'attachment; filename="' . $name . '.yml"',
    ];

    return new Response($data, 200, $headers);
  }

  /**
   * Format config schema as list table.
   *
   * @param string $config_name
   *   The config name.
   * @param array|object $config_schema
   *   An array of config elements with key.
   *
   * @return array
   *   The list table as a render array.
   */
  protected function formatList($config_name, $config_schema) {
    $rows = [];
    // Check compliance with the underlying primitives (string, boolean …).
    $errors = (array) $this->configInspectorManager->checkValues($config_name);
    // Check validatability (beyond primitives).
    $raw_validatability = $this->configInspectorManager->checkValidatabilityValues($config_name);
    // Check compliance with the validation constraints, if any.
    $raw_violations = $this->configInspectorManager->validateValues($config_name);
    $violations = ConfigInspectorManager::violationsToArray($raw_violations);
    $schema = $this->configInspectorManager->convertConfigElementToList($config_schema);
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();

      $property_path = $element->getPropertyPath();
      // @todo Remove once <= 10.0.x support is dropped.
      if (version_compare(\Drupal::VERSION, '10.1.0', 'lt')) {
        $property_path = $config_name . '.' . $property_path;
      }

      $rows[] = [
        'class' => isset($errors[$config_name . ':' . $key]) ? ['config-inspector-error'] : [],
        'data' => [
          ['class' => ['icon'], 'data' => ''],
          $key,
          $definition['label'],
          $definition['type'],
          $raw_validatability->getValidatabilityPerPropertyPath()[$property_path]
            ? $this->t('Yes')
            : $this->t('No'),
          $this->formatValue($element),
          @$errors[$config_name . ':' . $key] ?: '',
          !array_key_exists($key, $violations)
            ? ''
            : (!is_array($violations[$key])
              ? $violations[$key]
              : implode('<br>', $violations[$key])
          ),
        ],
      ];
    }
    return [
      '#attached' => ['library' => ['config_inspector/config_inspector']],
      '#type' => 'table',
      '#header' => [
        '',
        $this->t('Name'),
        $this->t('Label'),
        $this->t('Type'),
        $this->t('Validatable'),
        $this->t('Value'),
        $this->t('Error'),
        $this->t('Validation error'),
      ],
      '#rows' => $rows,
    ];
  }

  /**
   * Format config schema as a tree.
   *
   * @param string $config_name
   *   The config name.
   * @param array|object $schema
   *   The schema.
   * @param \Drupal\config_inspector\ConfigSchemaValidatability $validatability
   *   The associated validatability.
   * @param bool $collapsed
   *   (Optional) Indicates whether the details are collapsed by default.
   * @param string $base_key
   *   Prefix used in the description.
   *
   * @return array
   *   The tree in the form of a render array.
   *
   * @todo Remove $base_key argument once <=10.0.x support is dropped.
   */
  public function formatTree(string $config_name, $schema, ConfigSchemaValidatability $validatability, $collapsed = FALSE, $base_key = '') {
    $build = [];
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();
      $label = $definition['label'] ?: $this->t('N/A');
      $type = $definition['type'];
      $property_path = $element->getPropertyPath();
      $element_key = str_replace($element->getRoot()->getName() . '.', '', $property_path);
      // @todo Remove once <= 10.0.x support is dropped.
      if (version_compare(\Drupal::VERSION, '10.1.0', 'lt')) {
        $element_key = $base_key . $key;
        $property_path = $config_name . '.' . $property_path;
      }
      $is_validatable = $validatability->getValidatabilityPerPropertyPath()[$property_path];
      $is_validatable_string = $is_validatable
        ? $this->t('validatable')
        : '<s>' . $this->t('validatable') . '</s>';

      if ($element instanceof ArrayElement) {
        $build[$key] = [
          '#type' => 'details',
          '#title' => $label,
          '#description' => $element_key . ' (' . $type . ', ' . $is_validatable_string . ')',
          '#description_display' => 'after',
          '#open' => !$collapsed,
        ] + $this->formatTree($config_name, $element, $validatability, TRUE, $element_key . '.');
      }
      else {
        $build[$key] = [
          '#type' => 'item',
          '#title' => $label,
          '#plain_text' => $this->formatValue($element),
          '#description' => $element_key . ' (' . $type . ', ' . $is_validatable_string . ')',
          '#description_display' => 'after',
        ];
      }
      // For validatable properties, expand the description to show the
      // validation constraints.
      if ($is_validatable) {
        $all_constraints = $validatability->getConstraints($property_path);
        $local_constraints = array_map(
          fn (string $constraint_name, $constraints_options) => ['#markup' => sprintf("<code>%s</code>", trim(Yaml::encode([$constraint_name => $constraints_options])))],
          array_keys($all_constraints['local']),
          array_values($all_constraints['local'])
        );
        $inherited_constraints = array_map(
          fn (string $constraint_name, $constraints_options) => ['#markup' => sprintf('<span class="inherited"><code>%s</code> <small>→ %s</small></span>', trim(Yaml::encode([$constraint_name => $constraints_options])), $this->t('inherited'))],
          array_keys($all_constraints['inherited']),
          array_values($all_constraints['inherited'])
        );
        $constraints = array_merge($local_constraints, $inherited_constraints);
        $build[$key]['#description'] = [
          '#prefix' => $build[$key]['#description'],
          '#theme' => 'item_list',
          '#items' => $constraints,
          '#attributes' => [
            'class' => [
              'config-inspector--validation-constraints-list',
            ],
          ],
        ];
      }
      else {
        $build[$key]['#description'] = [
          '#prefix' => $build[$key]['#description'] . '<pre><code>',
          '#markup' => $validatability->computeValidatabilityTodo($property_path),
          '#suffix' => '</code><pre></pre>',
        ];
      }
    }
    return $build;
  }

  /**
   * Formats a value as a string, for readable output.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $element
   *   The value element.
   *
   * @return string
   *   The value in string form.
   */
  protected function formatValue(TypedDataInterface $element) {
    $value = $element->getValue();
    if (is_bool($value)) {
      return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
      return $value;
    }
    if (empty($value)) {
      return '<' . $this->t('empty') . '>';
    }
    return '<' . gettype($value) . '>';
  }

  /**
   * Helper function to dump data in a reasonably reviewable fashion.
   *
   * @param string $data
   *   The displayed data.
   * @param string $title
   *   (Optional) The title. Defaults to "Data'.
   *
   * @return array
   *   The render array.
   */
  protected function formatData($data, $title = 'Data') {
    if ($this->dumper && $this->moduleHandler->moduleExists('devel')) {
      $output = $this->dumper->export($data, $title);
    }
    else {
      $output = '<h2>' . $title . '</h2>';
      $output .= '<pre>';
      $output .= htmlspecialchars(var_export($data, TRUE));
      $output .= '</pre>';
      $output .= '<br />';
    }
    return [
      '#markup' => $output,
    ];
  }

}
