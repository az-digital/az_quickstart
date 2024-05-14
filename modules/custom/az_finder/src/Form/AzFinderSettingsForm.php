<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Messenger\Messenger;

/**
 * Form for custom Quickstart Finder module settings.
 */
class AZFinderSettingsForm extends ConfigFormBase {


  /**
   * Cached view options to minimize expensive operations.
   * This property holds an array of view display options that are using a
   * specific plugin. Once loaded, it is reused to avoid unnecessary reloads
   * during the lifecycle of the form.
   *
   * @var array|null
   */
  protected $viewOptions = NULL;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    Messenger $messenger
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('az_finder');
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_finder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_finder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   *
   */
  public function overrideSettingsCallback(array &$form, FormStateInterface $form_state) {

    print_r($form_state->getValues());
    \Drupal::logger('az_finder')->notice('AJAX callback triggered');
    $selected_view = $form_state->getValue(['az_finder_tid_widget', 'overrides', 'select_view_display_container', 'select_view_display']);
    [$view_id, $display_id] = explode(':', $selected_view);

    if (!empty($view_id) && !empty($display_id)) {
      $key = $view_id . '_' . $display_id;
      if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
        $form['az_finder_tid_widget']['overrides'][$key] = [
          '#type' => 'details',
          '#title' => $this->t("Override Settings for $view_id - $display_id"),
          '#open' => FALSE,
        ];
      }
    }

    $form_state->setRebuild(TRUE);

    return $form['az_finder_tid_widget']['overrides'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['az_finder_tid_widget'] = [
      'prefix' => [
        '#markup' => '<div id="js-az-finder-tid-widget-settings">',
      ],
      'suffix' => [
        '#markup' => '</div>',
      ],
      '#type' => 'details',
      '#title' => $this->t('Term ID Widget Settings'),
      '#open' => TRUE,
      '#description' => $this->t('Configure the default settings for term ID widgets.'),
    ];

    $form['az_finder_tid_widget']['default_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Default State Setting'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'disable' => $this->t('Disable'),
        'remove' => $this->t('Remove'),
        'expand' => $this->t('Expand'),
        'collapse' => $this->t('Collapse'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('Choose how term ID widgets should behave by default everywhere.'),
      '#config_target' => 'az_finder.settings:tid_widget.default_state',
    ];
    $form['az_finder_tid_widget']['overrides'] = [
      '#prefix' => '<div id="js-az-finder-tid-widget-overrides">',
      '#suffix' => '</div>',
  ];

    $form['az_finder_tid_widget']['overrides']['add_override'] = [
      '#type' => 'button',
      '#value' => $this->t('Add an Override'),
      '#ajax' => [
        'callback' => '::ajaxAddOverrideCallback',
        'wrapper' => 'js-az-finder-tid-widget-settings',
        'effect' => 'fade',
      ],
      '#weight' => 100,
    ];


    // Manage existing override settings.
    $overrides = $form_state->get('az_finder_tid_widget', 'overrides') ?? $this->getExistingOverrides();

    foreach ($overrides as $key => $override) {
      $this->addOverrideSection($form, $form_state, $key, $override['view_id'], $override['display_id']);
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   *
   */
  protected function addOverrideSection(&$form, FormStateInterface $form_state, $key, $view_id, $display_id) {
    // Ensure this section is only added once.
    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for $view_id - $display_id"),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
      ];
    }

    // Add vocabularies directly to this section.
    $vocabulary_ids = $this->getVocabularyIdsForAzFinderTidWidgetFilter($view_id, $display_id);
    foreach ($vocabulary_ids as $vocabulary_id) {
      $this->addVocabularySection($form['az_finder_tid_widget']['overrides'][$key], $vocabulary_id, $view_id, $display_id);
    }
  }

  /**
   *
   */
  public function ajaxAddOverrideCallback(array &$form, FormStateInterface $form_state) {
    // Ensure the 'overrides' container exists and is set up correctly.
    if (!isset($form['az_finder_tid_widget']['overrides']['select_view_display_container'] )) {
      $response = new AjaxResponse();
          $message = [
          '#theme' => 'status_messages',
          '#message_list' => $this->messenger->messagesByType('status'),

        ];


      $form['az_finder_tid_widget']['overrides'] = [
        '#type' => 'details',
        '#title' => $this->t('Override Settings'),
        '#open' => TRUE,
        '#prefix' => '<div id="js-az-finder-tid-widget-overrides">',
        '#suffix' => '</div>',
      ];

      $form['az_finder_tid_widget']['overrides']['select_view_display_container'] = [
        '#type' => 'container',
        '#prefix' => '<div class="container-inline">',
        '#suffix' => '</div>',
        'select_view_display' => [
          '#type' => 'select',
          '#title' => $this->t('Select View and Display'),
          '#options' => $this->getViewOptions(),
          '#empty_option' => $this->t('- Select -'),
          '#attributes' => ['id' => 'js-az-select-view-display'],
        ],
        'override' => [
          '#type' => 'button',
          '#value' => $this->t('Override'),
          '#ajax' => [
            'callback' => '::overrideSettingsCallback',
            'wrapper' => 'js-az-finder-tid-widget-overrides',
            'effect' => 'fade',
          ],
          '#states' => [
            'disabled' => [
              ':input[id="js-az-select-view-display"]' => ['value' => ''],
            ],
          ],
          '#suffix' => '<div id="az-finder-tid-widget-overrides-az-finder-event"></div>',
        ],
      ];
      $response->addCommand(new HtmlCommand('#result-message', $messages));
      $response->addCommand(new ReplaceCommand(NULL, $form));
        return $response;

    }

    $form_state->setRebuild(TRUE);
    return $form['az_finder_tid_widget']['overrides'];
  }

  /**
   *
   */
  protected function addVocabularySection(&$form_section, $vocabulary_id, $view_id, $display_id) {
    $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->load($vocabulary_id);
    if ($vocabulary) {
      $form_section['vocab_' . $vocabulary_id] = [
        '#type' => 'details',
        '#title' => $this->t('Vocabulary: %name', ['%name' => $vocabulary->label()]),
        '#open' => TRUE,
        '#description' => $this->t('Override how a specific term within the selected vocabulary will behave when it has child terms.'),

      ];
      $this->addTermsTable($form_section['vocab_' . $vocabulary_id], $vocabulary_id, $view_id, $display_id);
    }
  }

  /**
   *
   */
  protected function addTermsTable(&$form_section, $vocabulary_id, $view_id, $display_id) {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary_id);
    $config_id = "az_finder.tid_widget.$view_id.$display_id";
    $vocabulary_config_path = "$config_id:vocabularies.$vocabulary_id";

    $form_section[$vocabulary_id]['terms_table'] = [
      '#type' => 'table',
      '#header' => [$this->t('Term'), $this->t('Override')],
      '#empty' => $this->t('No terms found.'),
    ];

    foreach ($terms as $term) {
      $term_tid = $term->tid;
      $form_section[$vocabulary_id]['terms_table'][$term_tid]['term_name'] = [
        '#markup' => str_repeat('-', $term->depth) . $term->name,
      ];
      $form_section[$vocabulary_id]['terms_table'][$term_tid]['override'] = [
        '#type' => 'select',
        '#options' => [
          '' => $this->t('Default'),
          'hide' => $this->t('Hide'),
          'disable' => $this->t('Disable'),
          'remove' => $this->t('Remove'),
          'expand' => $this->t('Expand'),
          'collapse' => $this->t('Collapse'),
        ],
        '#config_target' => "$vocabulary_config_path.terms.$term_tid.default_state",
      ];
    }
  }

  /**
   * Retrieves vocabulary IDs for a view display.
   *
   * @param string $view_id
   *   The view ID.
   * @param string $display_id
   *   The display ID.
   *
   * @return array
   *   An array of vocabulary IDs.
   */
  protected function getVocabularyIdsForAzFinderTidWidgetFilter($view_id, $display_id) {
    $vocabulary_ids = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      $display = $view->getDisplay($display_id);
      $filters = $display['display_options']['filters'] ?? [];
      $exposed_form_options = $display['display_options']['exposed_form']['options'] ?? [];
      $filters = $exposed_form_options['bef']['filter'] ?? [];
      foreach ($filters as $filter_id => $filter_settings) {
        if (isset($filter_settings['plugin_id']) && $filter_settings['plugin_id'] === 'az_finder_tid_widget') {
          $vocabulary_ids = $this->getVocabularyIdsForFilter($view_id, $display_id, $filter_id);

          break;
        }
      }
    }
    return $vocabulary_ids;
  }

  /**
   *
   */
  protected function getVocabularyIdsForFilter($view_id, $display_id, $filter_id) {
    $vocabulary_ids = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      $display = $view->getDisplay($display_id);
      $filters = $display['display_options']['filters'] ?? [];

      // Check for a specific filter.
      if (isset($filters[$filter_id])) {
        $field_config = \Drupal::entityTypeManager()->getStorage('field_config')->loadByProperties(['field_name' => 'field_az_event_category']);
        $field_config = reset($field_config);

        if ($field_config) {
          // // Get the settings which include target bundles (vocabularies in this case)
          $settings = $field_config->getSettings();
          $target_bundles = $settings['handler_settings']['target_bundles'] ?? [];

          // Each target bundle is a vocabulary machine name.
          foreach ($target_bundles as $vocabulary_machine_name) {
            $vocabulary = Vocabulary::load($vocabulary_machine_name);
            if ($vocabulary) {
              $vocabulary_ids[] = $vocabulary->id();
            }
          }
        }
      }
    }

    return $vocabulary_ids;
  }

  /**
   *
   */
  protected function getExistingOverrides() {
    $config_names = $this->configFactory->listAll('az_finder.tid_widget.');
    $overrides = [];

    foreach ($config_names as $config_name) {
      $config = $this->config($config_name);
      $view_id_display_id = substr($config_name, strlen('az_finder.tid_widget.'));
      [$view_id, $display_id] = explode('.', $view_id_display_id);

      $overrides[$view_id . '_' . $display_id] = [
        'view_id' => $view_id,
        'display_id' => $display_id,
        'vocabularies' => $config->get('vocabularies') ?? [],
      ];
    }

    return $overrides;
  }

  /**
 * Retrieves views that use the specified plugin, with caching, excluding already configured views.
 *
 * This function ensures data is only fetched when necessary, and uses
 * Drupal's internal caching mechanism to store results for faster retrieval.
 * It also filters out views that have already been configured.
 *
 * @param string $plugin_id
 *   The plugin ID to check for.
 * @param bool $force_refresh
 *   Forces the refresh of the view options cache.
 *
 * @return array
 *   An array of views formatted as 'view_id:display_id' => 'view label (display_id)'.
 */
protected function getViewOptions(string $plugin_id = 'az_finder_tid_widget', bool $force_refresh = false): array {
    if ($this->viewOptions === null || $force_refresh) {
        $cache_id = 'az_finder:view_options:' . $plugin_id;
        $cached_data = \Drupal::cache()->get($cache_id);

        if ($cached_data && !$force_refresh) {
            $this->viewOptions = $cached_data->data;
        } else {
            // Fetch views using the plugin and exclude configured ones
            $this->viewOptions = $this->filterConfiguredViews($this->getViewsUsingPlugin($plugin_id));
            \Drupal::cache()->set($cache_id, $this->viewOptions, CacheBackendInterface::CACHE_PERMANENT, ['az_finder:view_options']);
        }
    }

    return $this->viewOptions;
}

/**
 * Filters out views and displays that are already configured.
 *
 * @param array $viewOptions
 *   An array of views and displays from getViewsUsingPlugin.
 *
 * @return array
 *   The filtered array of view options.
 */
protected function filterConfiguredViews(array $viewOptions): array {
    $existingConfigs = $this->getExistingViewDisplayConfigurations();
    foreach ($viewOptions as $key => $label) {
        if (isset($existingConfigs[$key])) {
            unset($viewOptions[$key]);
        }
    }
    return $viewOptions;
}

/**
 * Retrieves vocabulary IDs for a view display.
 *
 * @param string $view_id
 *   The view ID.
 * @param string $display_id
 *   The display ID.
 *
 * @return array
 *   An array of vocabulary IDs.
 */
protected function getExistingViewDisplayConfigurations() {
    $existingConfigs = [];
    $configs = $this->configFactory->listAll('az_finder.tid_widget.');
    foreach ($configs as $configName) {
        $parts = explode('.', $configName);
        // Assuming the configuration name format is 'az_finder.tid_widget.[view_id].[display_id]'
        if (count($parts) >= 4) {
            $viewId = $parts[2];
            $displayId = $parts[3];
            $existingConfigs["$viewId:$displayId"] = "$viewId - $displayId";
        }
    }
    return $existingConfigs;
}


  /**
   * Get views that use the plugin.
   *
   * This method fetches all views that are using the specified plugin
   * based on the provided plugin ID. It's optimized to be called through
   * getViewOptions for caching and control.
   *
   * @param string $plugin_id
   *   The plugin ID to check for.
   *
   * @return array
   *   An array of views that use the plugin, formatted as
   *   'view_id:display_id' => 'view label (display_id)'.
   */
  private function getViewsUsingPlugin(string $plugin_id): array {
    $options = [
      '' => '- Select -',
    ];
    $views = $this->entityTypeManager->getStorage('view')->loadMultiple();

    foreach ($views as $view) {
      $displays = $view->get('display') ?: [];
      foreach ($displays as $display_id => $display) {
        $exposed_form_options = $display['display_options']['exposed_form']['options'] ?? [];
        $filters = $exposed_form_options['bef']['filter'] ?? [];
        foreach ($filters as $filter_id => $filter_settings) {
          if (isset($filter_settings['plugin_id']) && $filter_settings['plugin_id'] === $plugin_id) {
            $options[$view->id() . ':' . $display_id] = $view->label() . ' (' . $display_id . ')';
            break;
          }
        }
      }
    }

    return $options;
  }
}
