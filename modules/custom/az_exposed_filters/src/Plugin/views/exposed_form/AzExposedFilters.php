<?php

namespace Drupal\az_exposed_filters\Plugin\views\exposed_form;

use Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\exposed_form\InputRequired;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "az-exposed-filters",
 *   title = @Translation("AZ Exposed Filters"),
 *   help = @Translation("Provides additional options for exposed form elements.")
 * )
 */
class AzExposedFilters extends InputRequired {

  /**
   * AZ Exposed Filters widget plugin manager.
   *
   * @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager
   */
  public $filterWidgetManager;

  /**
   * AZ Exposed Filters pager widget plugin manager.
   *
   * @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager
   */
  public $pagerWidgetManager;

  /**
   * BEF sort widget plugin manager.
   *
   * @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager
   */
  public $sortWidgetManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * AzExposedFilters constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager $filter_widget_manager
   *   The AZ exposed filter widget manager for filter widgets.
   * @param \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager $pager_widget_manager
   *   The AZ exposed filter widget manager for pager widgets.
   * @param \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetManager $sort_widget_manager
   *   The AZ exposed filter widget manager for sort widgets.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Manage drupal modules.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AzExposedFiltersWidgetManager $filter_widget_manager, AzExposedFiltersWidgetManager $pager_widget_manager, AzExposedFiltersWidgetManager $sort_widget_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->filterWidgetManager = $filter_widget_manager;
    $this->pagerWidgetManager = $pager_widget_manager;
    $this->sortWidgetManager = $sort_widget_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.az_exposed_filters_filter_widget'),
      $container->get('plugin.manager.az_exposed_filters_pager_widget'),
      $container->get('plugin.manager.az_exposed_filters_sort_widget'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // General, sort, pagers, and filter.
    $az_exposed_filters_options = [
      'general' => [
        'autosubmit' => FALSE,
        'autosubmit_exclude_textfield' => FALSE,
        'autosubmit_textfield_delay' => 500,
        'autosubmit_hide' => FALSE,
        'input_required' => FALSE,
        'allow_secondary' => FALSE,
        'secondary_label' => $this->t('Advanced options'),
        'secondary_open' => FALSE,
        'reset_button_always_show' => FALSE,
      ],
      'sort' => [
        'plugin_id' => 'default',
      ],
    ];

    // Initialize options if any sort is exposed.
    // Iterate over each sort and determine if any sorts are exposed.
    $is_sort_exposed = FALSE;
    /** @var \Drupal\views\Plugin\views\HandlerBase $sort */
    foreach ($this->view->display_handler->getHandlers('sort') as $sort) {
      if ($sort->isExposed()) {
        $is_sort_exposed = TRUE;
        break;
      }
    }
    if ($is_sort_exposed) {
      $az_exposed_filters_options['sort']['plugin_id'] = 'default';
    }

    // Initialize options if the pager is exposed.
    $pager = $this->view->getPager();
    if ($pager && $pager->usesExposed()) {
      $az_exposed_filters_options['pager']['plugin_id'] = 'default';
    }

    // Go through each exposed filter and set default format.
    /** @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->display_handler->getHandlers('filter') as $filter_id => $filter) {
      if (!$filter->isExposed()) {
        continue;
      }

      $az_exposed_filters_options['filter'][$filter_id]['plugin_id'] = 'default';
    }

    // Iterate over az_exposed_filters options and convert them to be compatible with views
    // default options.
    $options += $this->createOptionDefaults(['az_exposed_filters' => $az_exposed_filters_options]);

    return $options;
  }

  /**
   * Creates a list of view handler default options.
   *
   * Views handlers expect default options in a specific format.
   *
   * @param array $options
   *   An array of plugin defaults.
   *
   * @return array
   *   An array of plugin options.
   *
   * @see \Drupal\views\Plugin\views\PluginBase::setOptionDefaults
   */
  protected function createOptionDefaults(array $options) {
    $result = [];
    foreach ($options as $key => $option) {
      if (is_array($option)) {
        $result[$key]['contains'] = $this->createOptionDefaults($option);
      }
      else {
        $result[$key]['default'] = $option;
      }
    }

    return $result;
  }

  /**
   * Build the views options form and adds custom options for BEF.
   *
   * @inheritDoc
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    // Ensure that the form values are stored in their original location, and
    // not dependent on their position in the form tree. We are moving around
    // a few elements to make the UI more user friendly.
    $original_form = [];
    parent::buildOptionsForm($original_form, $form_state);
    foreach (Element::children($original_form) as $element) {
      $original_form[$element]['#parents'] = ['exposed_form_options', $element];
    }

    // Save shorthand for BEF options.
    $az_exposed_filters_options = $this->options['az_exposed_filters'];

    // User raw user input for AJAX callbacks.
    $user_input = $form_state->getUserInput();
    $az_exposed_filters_input = $user_input['exposed_form_options']['az_exposed_filters'] ?? NULL;

    /*
     * General BEF settings
     */
    // Reorder some existing form elements.
    $form['az_exposed_filters']['general']['submit_button'] = $original_form['submit_button'];
    $form['az_exposed_filters']['general']['reset_button'] = $original_form['reset_button'];
    $form['az_exposed_filters']['general']['reset_button_label'] = $original_form['reset_button_label'];

    $form['az_exposed_filters']['general']['reset_button_always_show'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always show reset button'),
      '#description' => $this->t('Will keep the reset button visible even without user input.'),
      '#default_value' => $az_exposed_filters_options['general']['reset_button_always_show'],
      '#states' => [
        'invisible' => [
          'input[name="exposed_form_options[reset_button]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    // Add the 'auto-submit' functionality.
    $form['az_exposed_filters']['general']['autosubmit'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable auto-submit'),
      '#description' => $this->t('Automatically submits the form when an element has changed.'),
      '#default_value' => $az_exposed_filters_options['general']['autosubmit'],
    ];

    $form['az_exposed_filters']['general']['autosubmit_exclude_textfield'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude Textfield'),
      '#description' => $this->t('Exclude textfields from auto-submit. User will have to press enter key, or click submit button.'),
      '#default_value' => $az_exposed_filters_options['general']['autosubmit_exclude_textfield'],
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][autosubmit]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['az_exposed_filters']['general']['autosubmit_textfield_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay for textfield autosubmit'),
      '#description' => $this->t('Configure a delay in ms before triggering autosubmit on textfields.'),
      '#default_value' => $az_exposed_filters_options['general']['autosubmit_textfield_delay'],
      '#min' => 0,
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][autosubmit]"]' => ['checked' => TRUE],
          ':input[name="exposed_form_options[az_exposed_filters][general][autosubmit_exclude_textfield]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['az_exposed_filters']['general']['autosubmit_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide submit button'),
      '#description' => $this->t('Hides submit button if auto-submit and javascript are enabled.'),
      '#default_value' => $az_exposed_filters_options['general']['autosubmit_hide'],
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][autosubmit]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Insert a checkbox to make the input required optional just before the
    // input required text field. Only show the text field if the input required
    // option is selected.
    $form['az_exposed_filters']['general']['input_required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Input required'),
      '#description' => $this->t('Only display results after the user has selected a filter option.'),
      '#default_value' => $az_exposed_filters_options['general']['input_required'],
    ];
    $original_form['text_input_required'] += [
      '#states' => [
        'visible' => [
          'input[name="exposed_form_options[az_exposed_filters][general][input_required]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['az_exposed_filters']['general']['text_input_required'] = $original_form['text_input_required'];

    /*
     * Allow exposed form items to be displayed as secondary options.
     */
    $form['az_exposed_filters']['general']['allow_secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable secondary exposed form options'),
      '#default_value' => $az_exposed_filters_options['general']['allow_secondary'],
      '#description' => $this->t('Allows you to specify some exposed form elements as being secondary options and places those elements in a collapsible "details" element. Use this option to place some exposed filters in an "Advanced Search" area of the form, for example.'),
    ];
    $form['az_exposed_filters']['general']['secondary_label'] = [
      '#type' => 'textfield',
      '#default_value' => $az_exposed_filters_options['general']['secondary_label'],
      '#title' => $this->t('Secondary options label'),
      '#description' => $this->t(
        'The name of the details element to hold secondary options. This cannot be left blank or there will be no way to show/hide these options.'
      ),
      '#states' => [
        'required' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['az_exposed_filters']['general']['secondary_open'] = [
      '#type' => 'checkbox',
      '#default_value' => $az_exposed_filters_options['general']['secondary_open'],
      '#title' => $this->t('Secondary option open by default'),
      '#description' => $this->t('Indicates whether the details element should be open by default.'),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[az_exposed_filters][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /*
     * Add options for exposed sorts.
     */
    // Add intro explaining BEF sorts.
    $documentation_uri = Url::fromUri('http://drupal.org/node/1701012')->toString();
    $form['az_exposed_filters']['sort']['az_exposed_filters_intro'] = [
      '#markup' => '<h3>' . $this->t('Exposed Sort Settings') . '</h3><p>' . $this->t('This section lets you select additional options for exposed sorts. Some options are only available in certain situations. If you do not see the options you expect, please see the <a href=":link">BEF settings documentation page</a> for more details.', [':link' => $documentation_uri]) . '</p>',
    ];

    // Iterate over each sort and determine if any sorts are exposed.
    $is_sort_exposed = FALSE;
    /** @var \Drupal\views\Plugin\views\HandlerBase $sort */
    foreach ($this->view->display_handler->getHandlers('sort') as $sort) {
      if ($sort->isExposed()) {
        $is_sort_exposed = TRUE;
        break;
      }
    }

    $form['az_exposed_filters']['sort']['empty'] = [
      '#type' => 'item',
      '#description' => $this->t('No sort elements have been exposed yet.'),
      '#access' => !$is_sort_exposed,
    ];

    if ($is_sort_exposed) {
      $options = [];
      foreach ($this->sortWidgetManager->getDefinitions() as $plugin_id => $definition) {
        if ($definition['class']::isApplicable()) {
          $options[$plugin_id] = $definition['label'];
        }
      }

      $form['az_exposed_filters']['sort']['configuration'] = [
        '#prefix' => "<div id='az-exposed-filters-sort-configuration'>",
        '#suffix' => "</div>",
        '#type' => 'container',
      ];

      // Get selected plugin_id on AJAX callback directly from the form state.
      $selected_plugin_id = $az_exposed_filters_input['sort']['configuration']['plugin_id'] ??
        $az_exposed_filters_options['sort']['plugin_id'];

      $form['az_exposed_filters']['sort']['configuration']['plugin_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Display exposed sort options as'),
        '#default_value' => $selected_plugin_id,
        '#options' => $options,
        '#description' => $this->t('Select a format for the exposed sort options.'),
        '#ajax' => [
          'event' => 'change',
          'effect' => 'fade',
          'progress' => 'throbber',
          // Since views options forms are complex, they're built by
          // Drupal in a different way. To bypass this problem we need to
          // provide the full path to the Ajax callback.
          'callback' => __CLASS__ . '::ajaxCallback',
          'wrapper' => 'az-exposed-filters-sort-configuration',
        ],
      ];

      // Move some existing form elements.
      $form['az_exposed_filters']['sort']['configuration']['exposed_sorts_label'] = $original_form['exposed_sorts_label'];
      $form['az_exposed_filters']['sort']['configuration']['expose_sort_order'] = $original_form['expose_sort_order'];
      $form['az_exposed_filters']['sort']['configuration']['sort_asc_label'] = $original_form['sort_asc_label'];
      $form['az_exposed_filters']['sort']['configuration']['sort_desc_label'] = $original_form['sort_desc_label'];

      if ($selected_plugin_id) {
        $plugin_configuration = $az_exposed_filters_options['sort'] ?? [];
        /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
        $plugin = $this->sortWidgetManager->createInstance($selected_plugin_id, $plugin_configuration);
        $plugin->setView($this->view);

        $subform = &$form['az_exposed_filters']['sort']['configuration'];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $subform += $plugin->buildConfigurationForm($subform, $subform_state);
      }
    }

    /*
     * Add options for exposed pager.
     */
    $documentation_uri = Url::fromUri('http://drupal.org/node/1701012')->toString();
    $form['az_exposed_filters']['pager']['az_exposed_filters_intro'] = [
      '#markup' => '<h3>' . $this->t('Exposed Pager Settings') . '</h3><p>' . $this->t('This section lets you select additional options for exposed pagers. Some options are only available in certain situations. If you do not see the options you expect, please see the <a href=":link">BEF settings documentation page</a> for more details.', [':link' => $documentation_uri]) . '</p>',
    ];
    $pager = $this->view->getPager();
    $is_pager_exposed = $pager && $pager->usesExposed();

    $form['az_exposed_filters']['pager']['empty'] = [
      '#type' => 'item',
      '#description' => $this->t('No pager elements have been exposed yet.'),
      '#access' => !$is_pager_exposed,
    ];

    if ($is_pager_exposed) {
      $options = [];

      foreach ($this->pagerWidgetManager->getDefinitions() as $plugin_id => $definition) {
        if ($definition['class']::isApplicable()) {
          $options[$plugin_id] = $definition['label'];
        }
      }

      $form['az_exposed_filters']['pager']['configuration'] = [
        '#prefix' => "<div id='az-exposed-filters-pager-configuration'>",
        '#suffix' => "</div>",
        '#type' => 'container',
      ];

      // Get selected plugin_id on AJAX callback directly from the form state.
      $selected_plugin_id = $az_exposed_filters_input['pager']['configuration']['plugin_id'] ??
        $az_exposed_filters_options['pager']['plugin_id'];

      $form['az_exposed_filters']['pager']['configuration']['plugin_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Display exposed pager options as'),
        '#default_value' => $selected_plugin_id,
        '#options' => $options,
        '#description' => $this->t('Select a format for the exposed pager options.'),
        '#ajax' => [
          'event' => 'change',
          'effect' => 'fade',
          'progress' => 'throbber',
          // Since views options forms are complex, they're built by
          // Drupal in a different way. To bypass this problem we need to
          // provide the full path to the Ajax callback.
          'callback' => __CLASS__ . '::ajaxCallback',
          'wrapper' => 'az-exposed-filters-pager-configuration',
        ],
      ];

      if ($selected_plugin_id) {
        $plugin_configuration = $az_exposed_filters_options['pager'] ?? [];
        /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
        $plugin = $this->pagerWidgetManager->createInstance($selected_plugin_id, $plugin_configuration);
        $plugin->setView($this->view);

        $subform = &$form['az_exposed_filters']['pager']['configuration'];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $subform += $plugin->buildConfigurationForm($subform, $subform_state);
      }
    }

    /*
     * Add options for exposed filters.
     */
    $documentation_uri = Url::fromUri('http://drupal.org/node/1701012')->toString();
    $form['az_exposed_filters']['filter']['az_exposed_filters_intro'] = [
      '#markup' => '<h3>' . $this->t('Exposed Filter Settings') . '</h3><p>' . $this->t('This section lets you select additional options for exposed filters. Some options are only available in certain situations. If you do not see the options you expect, please see the <a href=":link">BEF settings documentation page</a> for more details.', [':link' => $documentation_uri]) . '</p>',
    ];

    // Iterate over each filter and add BEF filter options.
    /** @var \Drupal\views\Plugin\views\HandlerBase $filter */
    foreach ($this->view->display_handler->getHandlers('filter') as $filter_id => $filter) {
      if (!$filter->isExposed()) {
        continue;
      }

      $options = [];
      foreach ($this->filterWidgetManager->getDefinitions() as $plugin_id => $definition) {
        if ($definition['class']::isApplicable($filter, $this->displayHandler->handlers['filter'][$filter_id]->options)) {
          $options[$plugin_id] = $definition['label'];
        }
      }

      // Alter the list of available widgets for this filter.
      $this->moduleHandler->alter('az_exposed_filters_display_options', $options, $filter);

      // Get a descriptive label for the filter.
      $label = $this->t('Exposed filter @filter', [
        '@filter' => $filter->options['expose']['identifier'],
      ]);
      if (!empty($filter->options['expose']['label'])) {
        $label = $this->t('Exposed filter "@filter" with label "@label"', [
          '@filter' => $filter->options['expose']['identifier'],
          '@label' => $filter->options['expose']['label'],
        ]);
      }
      $form['az_exposed_filters']['filter'][$filter_id] = [
        '#type' => 'details',
        '#title' => $label,
        '#collapsed' => FALSE,
        '#collapsible' => TRUE,
      ];

      $form['az_exposed_filters']['filter'][$filter_id]['configuration'] = [
        '#prefix' => "<div id='az-exposed-filters-filter-$filter_id-configuration'>",
        '#suffix' => "</div>",
        '#type' => 'container',
      ];

      // Get selected plugin_id on AJAX callback directly from the form state.
      $selected_plugin_id = $az_exposed_filters_input['filter'][$filter_id]['configuration']['plugin_id'] ?? $az_exposed_filters_options['filter'][$filter_id]['plugin_id'];

      $form['az_exposed_filters']['filter'][$filter_id]['configuration']['plugin_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Exposed filter widget:'),
        '#default_value' => $selected_plugin_id,
        '#options' => $options,
        '#ajax' => [
          'event' => 'change',
          'effect' => 'fade',
          'progress' => 'throbber',
          // Since views options forms are complex, they're built by
          // Drupal in a different way. To bypass this problem we need to
          // provide the full path to the Ajax callback.
          'callback' => __CLASS__ . '::ajaxCallback',
          'wrapper' => 'az-exposed-filters-filter-' . $filter_id . '-configuration',
        ],
      ];

      if ($selected_plugin_id) {
        $plugin_configuration = $az_exposed_filters_options['filter'][$filter_id] ?? [];
        /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
        $plugin = $this->filterWidgetManager->createInstance($selected_plugin_id, $plugin_configuration);
        $plugin->setView($this->view);
        $plugin->setViewsHandler($filter);

        $subform = &$form['az_exposed_filters']['filter'][$filter_id]['configuration'];
        $subform_state = SubformState::createForSubform($subform, $form, $form_state);
        $subform += $plugin->buildConfigurationForm($subform, $subform_state);
      }
    }
  }

  /**
   * The form ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form element to return.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($triggering_element['#array_parents'], 0, -1));
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    // Drupal only passes in a part of the views form, but we need the complete
    // form array for plugin subforms to work.
    $parent_form = $form_state->getCompleteForm();
    // Save a shorthand to the BEF form.
    $az_exposed_filters_form = &$form['az_exposed_filters'];
    // Save a shorthand to the BEF options.
    $az_exposed_filters_form_options = $form_state->getValue(['exposed_form_options', 'az_exposed_filters']);

    parent::validateOptionsForm($form, $form_state);

    // Skip plugin validation if we are switching between az_exposed_filters plugins.
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#type'] !== 'submit') {
      return;
    }

    // Shorthand for all filter handlers in this view.
    /** @var \Drupal\views\Plugin\views\HandlerBase[] $filters */
    $filters = $this->view->display_handler->handlers['filter'];

    // Iterate over all filter, sort and pager plugins.
    foreach ($az_exposed_filters_form_options as $type => $config) {
      // Validate exposed filter configuration.
      if ($type === 'filter') {
        foreach ($config as $filter_id => $filter_options) {
          $plugin_id = $filter_options['configuration']['plugin_id'] ?? NULL;
          if (!$plugin_id) {
            continue;
          }
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->filterWidgetManager->createInstance($plugin_id);
          $subform = &$az_exposed_filters_form[$type][$filter_id]['configuration'];
          $subform_state = SubformState::createForSubform($subform, $parent_form, $form_state);
          $plugin->setView($this->view);
          $plugin->setViewsHandler($filters[$filter_id]);
          $plugin->validateConfigurationForm($subform, $subform_state);
        }
      }
      // Validate exposed pager/sort configuration.
      elseif (in_array($type, ['pager', 'sort'])) {
        $plugin_id = $config['configuration']['plugin_id'] ?? NULL;
        if (!$plugin_id) {
          continue;
        }

        // Use the correct widget manager.
        if ($type === 'pager') {
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->pagerWidgetManager->createInstance($plugin_id);
        }
        else {
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->sortWidgetManager->createInstance($plugin_id);
        }

        $subform = &$az_exposed_filters_form[$type]['configuration'];
        $subform_state = SubformState::createForSubform($subform, $parent_form, $form_state);
        $plugin->setView($this->view);
        $plugin->validateConfigurationForm($subform, $subform_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    // Drupal only passes in a part of the views form, but we need the complete
    // form array for plugin subforms to work.
    $parent_form = $form_state->getCompleteForm();
    // Save a shorthand to the BEF form.
    $az_exposed_filters_form = &$form['az_exposed_filters'];

    // Reorder options based on config - some keys may have shifted because of
    // form alterations (@see \Drupal\az_exposed_filters\Plugin\views\exposed_form\AzExposedFilters::buildOptionsForm).
    $options = &$form_state->getValue('exposed_form_options');
    $options = array_replace_recursive($this->options, $options);
    // Save a shorthand to the BEF options.
    $az_exposed_filters_options = &$options['az_exposed_filters'];

    // Shorthand for all filter handlers in this view.
    /** @var \Drupal\views\Plugin\views\HandlerBase[] $filters */
    $filters = $this->view->display_handler->handlers['filter'];

    parent::submitOptionsForm($form, $form_state);

    // Iterate over all filter, sort and pager plugins.
    foreach ($az_exposed_filters_options as $type => $config) {
      // Save exposed filter configuration.
      if ($type === 'filter') {
        foreach ($config as $filter_id => $filter_options) {
          $plugin_id = $filter_options['configuration']['plugin_id'] ?? NULL;
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          if (!$plugin_id) {
            unset($az_exposed_filters_options['filter'][$filter_id]);
            continue;
          }

          $plugin = $this->filterWidgetManager->createInstance($plugin_id);
          $subform = &$az_exposed_filters_form[$type][$filter_id]['configuration'];
          $subform_state = SubformState::createForSubform($subform, $parent_form, $form_state);
          $plugin->setView($this->view);
          $plugin->setViewsHandler($filters[$filter_id]);
          $plugin->submitConfigurationForm($subform, $subform_state);

          $plugin_configuration = $plugin->getConfiguration();
          $az_exposed_filters_options[$type][$filter_id] = $plugin_configuration;
        }
      }
      // Save exposed pager/sort configuration.
      elseif (in_array($type, ['pager', 'sort'])) {
        $plugin_id = $config['configuration']['plugin_id'] ?? NULL;
        if (!$plugin_id) {
          unset($az_exposed_filters_options[$type]);
          continue;
        }

        // Use the correct widget manager.
        if ($type === 'pager') {
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->pagerWidgetManager->createInstance($plugin_id);
        }
        else {
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->sortWidgetManager->createInstance($plugin_id);
        }

        $subform = &$az_exposed_filters_form[$type]['configuration'];
        $subform_state = SubformState::createForSubform($subform, $parent_form, $form_state);
        $plugin->setView($this->view);
        $plugin->submitConfigurationForm($subform, $subform_state);

        $plugin_configuration = $plugin->getConfiguration();
        $az_exposed_filters_options[$type] = $plugin_configuration;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    // Mark form as AZ Exposed Filter form for easier alterations.
    $form['#context']['az_exposed_filters'] = TRUE;

    // These styles are used on all exposed forms.
    $form['#attached']['library'][] = 'az_exposed_filters/general';

    // Add the az-exposed-filters-exposed-form class at the form level so we can limit some
    // styling changes to just BEF forms.
    $form['#attributes']['class'][] = 'az-exposed-filters-exposed-form';

    // Grab BEF options and allow modules/theme to modify them before
    // processing.
    $az_exposed_filters_options = $this->options['az_exposed_filters'];
    $this->moduleHandler->alter('az_exposed_filters_options', $az_exposed_filters_options, $this->view, $this->displayHandler);

    // Apply auto-submit values.
    if (!empty($az_exposed_filters_options['general']['autosubmit'])) {
      $form = array_merge_recursive($form, [
        '#attributes' => [
          'data-az-exposed-filters-auto-submit-full-form' => '',
          'data-az-exposed-filters-auto-submit' => '',
          'data-az-exposed-filters-auto-submit-delay' => $az_exposed_filters_options['general']['autosubmit_textfield_delay'],
        ],
      ]);
      $form['actions']['submit']['#attributes']['data-az-exposed-filters-auto-submit-click'] = '';
      $form['#attached']['library'][] = 'az_exposed_filters/auto_submit';

      if (!empty($az_exposed_filters_options['general']['autosubmit_exclude_textfield'])) {
        $supported_types = ['entity_autocomplete', 'textfield'];
        foreach ($form as &$element) {
          $element_type = $element['#type'] ?? NULL;
          if (in_array($element_type, $supported_types)) {
            $element['#attributes']['data-az-exposed-filters-auto-submit-exclude'] = '';
          }
        }
      }

      if (!empty($az_exposed_filters_options['general']['autosubmit_hide'])) {
        $form['actions']['submit']['#attributes']['class'][] = 'js-hide';
      }
    }

    // Some elements may be placed in a secondary details element (eg: "Advanced
    // search options"). Place this after the exposed filters and before the
    // rest of the items in the exposed form.
    $allow_secondary = $az_exposed_filters_options['general']['allow_secondary'];
    if ($allow_secondary) {
      $form['secondary'] = [
        '#attributes' => [
          'class' => ['az-exposed-filters--secondary'],
        ],
        '#type' => 'details',
        '#title' => $az_exposed_filters_options['general']['secondary_label'],
        '#open' => $az_exposed_filters_options['general']['secondary_open'],
        // Disable until fields are added to this fieldset.
        '#access' => FALSE,
      ];
    }

    /*
     * Handle exposed sort elements.
     */
    if (isset($az_exposed_filters_options['sort']['plugin_id']) && !empty($form['sort_by'])) {
      $plugin_id = $az_exposed_filters_options['sort']['plugin_id'];
      $plugin_configuration = $az_exposed_filters_options['sort'];

      /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
      $plugin = $this->sortWidgetManager->createInstance($plugin_id, $plugin_configuration);
      $plugin->setView($this->view);
      $plugin->exposedFormAlter($form, $form_state);
    }

    /*
     * Handle exposed pager elements.
     */
    $pager = $this->view->getPager();
    $is_pager_exposed = $pager && $pager->usesExposed();
    if ($is_pager_exposed && !empty($az_exposed_filters_options['pager']['plugin_id'])) {
      $plugin_id = $az_exposed_filters_options['pager']['plugin_id'];
      $plugin_configuration = $az_exposed_filters_options['pager'];

      /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
      $plugin = $this->pagerWidgetManager->createInstance($plugin_id, $plugin_configuration);
      $plugin->setView($this->view);
      $plugin->exposedFormAlter($form, $form_state);
    }

    /*
     * Handle exposed filters.
     */

    // Shorthand for all filter handlers in this view.
    /** @var \Drupal\views\Plugin\views\HandlerBase[] $filters */
    $filters = $this->view->display_handler->handlers['filter'];

    // Iterate over all exposed filters.
    if (!empty($az_exposed_filters_options['filter'])) {
      foreach ($az_exposed_filters_options['filter'] as $filter_id => $filter_options) {
        // Sanity check: Ensure this filter is an exposed filter.
        if (empty($filters[$filter_id]) || !$filters[$filter_id]->isExposed()) {
          continue;
        }

        $plugin_id = $filter_options['plugin_id'];
        if ($plugin_id) {
          /** @var \Drupal\az_exposed_filters\Plugin\AzExposedFiltersWidgetInterface $plugin */
          $plugin = $this->filterWidgetManager->createInstance($plugin_id, $filter_options);
          $plugin->setView($this->view);
          $plugin->setViewsHandler($filters[$filter_id]);
          $plugin->exposedFormAlter($form, $form_state);
        }
      }
    }

    // If our form has no visible filters, hide the submit button.
    $has_visible_filters = !empty(Element::getVisibleChildren($form)) ?: FALSE;
    $form['actions']['submit']['#access'] = $has_visible_filters;

    if ($az_exposed_filters_options['general']['reset_button_always_show']) {
      $form['actions']['reset']['#access'] = TRUE;
    }

    // Never enable a reset button that has already been disabled.
    if (!isset($form['actions']['reset']['#access']) || $form['actions']['reset']['#access'] === TRUE) {
      $form['actions']['reset']['#access'] = $has_visible_filters;
    }

    // Ensure default process/pre_render callbacks are included when a BEF
    // widget has added their own.
    foreach (Element::children($form) as $key) {
      $element = &$form[$key];
      $this->addDefaultElementInfo($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function exposedFilterApplied() {
    // If the input required option is set, check to see if a filter option has
    // been set.
    if (!empty($this->options['az_exposed_filters']['general']['input_required'])) {
      return parent::exposedFilterApplied();
    }
    else {
      return TRUE;
    }
  }

  /**
   * Inserts a new form element before another element identified by $key.
   *
   * This can be useful when reordering existing form elements without weights.
   *
   * @param array $form
   *   The form array to insert the element into.
   * @param string $key
   *   The key of the form element you want to prepend the new form element.
   * @param array $element
   *   The form element to insert.
   *
   * @return array
   *   The form array containing the newly inserted element.
   */
  protected function prependFormElement(array $form, $key, array $element) {
    $pos = array_search($key, array_keys($form)) + 1;
    return array_splice($form, 0, $pos - 1) + $element + $form;
  }

  /**
   * Adds default element callbacks.
   *
   * This is a workaround where adding process and pre-render functions are not
   * results in replacing the default ones instead of merging.
   *
   * @param array $element
   *   The render array for a single form element.
   *
   * @todo remove once the following issues are resolved.
   * @see https://www.drupal.org/project/drupal/issues/2070131
   * @see https://www.drupal.org/project/drupal/issues/2190333
   */
  protected function addDefaultElementInfo(array &$element) {
    /** @var \Drupal\Core\Render\ElementInfoManager $element_info_manager */
    $element_info = \Drupal::service('element_info');
    if (isset($element['#type']) && empty($element['#defaults_loaded']) && ($info = $element_info->getInfo($element['#type']))) {
      $element['#process'] = $element['#process'] ?? [];
      $element['#pre_render'] = $element['#pre_render'] ?? [];
      if (!empty($info['#process'])) {
        $element['#process'] = array_merge($info['#process'], $element['#process']);
      }
      if (!empty($info['#pre_render'])) {
        $element['#pre_render'] = array_merge($info['#pre_render'], $element['#pre_render']);
      }

      // Some processing needs to happen prior to the default form element
      // callbacks (e.g. sort). We use the custom '#pre_process' array for this.
      if (!empty($element['#pre_process'])) {
        $element['#process'] = array_merge($element['#pre_process'], $element['#process']);
      }

      // Workaround to add support for #group FAPI to all elements currently not
      // supported.
      // @todo remove once core issue is resolved.
      // @see https://www.drupal.org/project/drupal/issues/2190333
      if (!in_array('processGroup', array_column($element['#process'], 1))) {
        $element['#process'][] = ['\Drupal\Core\Render\Element\RenderElement', 'processGroup'];
        $element['#pre_render'][] = ['\Drupal\Core\Render\Element\RenderElement', 'preRenderGroup'];
      }
    }

    // Apply the same to any nested children.
    foreach (Element::children($element) as $key) {
      $child = &$element[$key];
      $this->addDefaultElementInfo($child);
    }
  }

}
