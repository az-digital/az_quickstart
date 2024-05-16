<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom Quickstart Finder module settings.
 */
class AZFinderSettingsForm extends ConfigFormBase {

  /**
   * Cached view options to minimize expensive operations.
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
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
    Messenger $messenger,
    CacheBackendInterface $cache_backend
  ) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get('az_finder');
    $this->messenger = $messenger;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('cache.default')
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
   * Callback to add override settings.
   */
  public function overrideSettingsCallback(array &$form, FormStateInterface $form_state) {
    $selected_view = $form_state->getValue(['az_finder_tid_widget', 'overrides', 'select_view_display_container', 'select_view_display']);
dpm('selected_view', $selected_view);
dpm($form_state->get('select_view_display'));

    if ($selected_view && strpos($selected_view, ':') !== false) {
      [$view_id, $display_id] = explode(':', $selected_view);

      if (!empty($view_id) && !empty($display_id)) {
        $key = $view_id . '_' . $display_id;
        $overrides = $form_state->get('overrides');
        $overrides[$key] = ['view_id' => $view_id, 'display_id' => $display_id];
        $form_state->set('overrides', $overrides);
        $this->addOverrideSection($form, $form_state, $key, $view_id, $display_id);
      }

      $form_state->setRebuild(TRUE);

      if (isset($form['az_finder_tid_widget']['overrides'])) {
        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#js-az-finder-tid-widget-overrides-container', $form['az_finder_tid_widget']['overrides']));
        return $response;
      }
      else {
        $this->logger('az_finder')->error('Overrides section not found in form array.');
      }
    }

    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['az_finder_tid_widget'] = [
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
      '#prefix' => '<div id="js-az-finder-tid-widget-overrides-container">',
      '#suffix' => '</div>',
    ];

    // Initialize overrides in the form state if not already set.
    if ($form_state->has('overrides') === FALSE) {
      $overrides = $this->getExistingOverrides();
      $form_state->set('overrides', $overrides);
    } else {
      $overrides = $form_state->get('overrides');
    }

    // Add existing overrides to the form.
    foreach ($overrides as $key => $override) {
      $this->addOverrideSection($form, $form_state, $key, $override['view_id'], $override['display_id']);
    }

    // Always show the view selection form.
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
          'wrapper' => 'js-az-finder-tid-widget-overrides-container',
          'effect' => 'fade',
        ],
        '#states' => [
          'disabled' => [
            ':input[id="js-az-select-view-display"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Adds an override section to the form.
   */
  protected function addOverrideSection(&$form, FormStateInterface $form_state, $key, $view_id, $display_id) {
    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for $view_id - $display_id"),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
      ];

      $vocabulary_ids = $this->getVocabularyIdsForAzFinderTidWidgetFilter($view_id, $display_id);
      foreach ($vocabulary_ids as $vocabulary_id) {
        $this->addVocabularySection($form['az_finder_tid_widget']['overrides'][$key], $vocabulary_id, $view_id, $display_id);
      }
    }
  }

  /**
   * Adds a vocabulary section to the form.
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
   * Adds a terms table to the form section.
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
   * Retrieves vocabulary IDs for a specific filter.
   */
  protected function getVocabularyIdsForFilter($view_id, $display_id, $filter_id) {
    $vocabulary_ids = [];
    $view = $this->entityTypeManager->getStorage('view')->load($view_id);
    if ($view) {
      $display = $view->getDisplay($display_id);
      $filters = $display['display_options']['filters'] ?? [];

      // Check for a specific filter.
      if (isset($filters[$filter_id])) {
        $field_config = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['field_name' => 'field_az_event_category']);
        $field_config = reset($field_config);

        if ($field_config) {
          $settings = $field_config->getSettings();
          $target_bundles = $settings['handler_settings']['target_bundles'] ?? [];

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
   * Retrieves existing override configurations.
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
   * Retrieves views that use the specified plugin, with caching.
   */
  protected function getViewOptions(string $plugin_id = 'az_finder_tid_widget', bool $force_refresh = FALSE): array {
    if ($this->viewOptions === NULL || $force_refresh) {
      $cache_id = 'az_finder:view_options:' . $plugin_id;
      $cached_data = $this->cacheBackend->get($cache_id);

      if ($cached_data && !$force_refresh) {
        $this->viewOptions = $cached_data->data;
      }
      else {
        $this->viewOptions = $this->filterConfiguredViews($this->getViewsUsingPlugin($plugin_id));
        $this->cacheBackend->set($cache_id, $this->viewOptions, CacheBackendInterface::CACHE_PERMANENT, ['az_finder:view_options']);
      }
    }

    return $this->viewOptions;
  }

  /**
   * Filters out views and displays that are already configured.
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
   * Retrieves existing view display configurations.
   */
  protected function getExistingViewDisplayConfigurations() {
    $existingConfigs = [];
    $configs = $this->configFactory->listAll('az_finder.tid_widget.');
    foreach ($configs as $configName) {
      $parts = explode('.', $configName);
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
