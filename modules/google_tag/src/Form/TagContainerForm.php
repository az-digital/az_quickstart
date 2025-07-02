<?php

namespace Drupal\google_tag\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\google_tag\GoogleTagEventManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Google tag container settings form.
 *
 * @property \Drupal\google_tag\Entity\TagContainer $entity
 */
class TagContainerForm extends EntityForm {

  // @todo move this into something pluggable.
  use GoogleTagManagerSettingsTrait;
  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected ConditionManager $conditionManager;

  /**
   * Context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected ContextRepositoryInterface $contextRepository;

  /**
   * Google Tag Events Plugin Manager.
   *
   * @var \Drupal\google_tag\GoogleTagEventManager
   */
  protected GoogleTagEventManager $tagEventManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a ContainerForm object.
   *
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The ConditionManager for building the insertion conditions.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   Context repository.
   * @param \Drupal\google_tag\GoogleTagEventManager $tag_event_manager
   *   Tag event manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager.
   */
  public function __construct(ConditionManager $condition_manager, ContextRepositoryInterface $context_repository, GoogleTagEventManager $tag_event_manager, LanguageManagerInterface $language_manager) {
    $this->conditionManager = $condition_manager;
    $this->contextRepository = $context_repository;
    $this->tagEventManager = $tag_event_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('plugin.manager.google_tag_event'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);

    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $accounts_wrapper_id = Html::getUniqueId('accounts-add-more-wrapper');
    $form['accounts_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Google Tag ID(s)'),
      '#prefix' => '<div id="' . $accounts_wrapper_id . '">',
      '#description' => $this->t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxx-yy, G-xxxxxxxx, AW-xxxxxxxxx, or DC-xxxxxxxx. To get a Web Property ID, <a href=":analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href=":webpropertyid">Find more information in the documentation</a>.', [
        ':analytics' => 'https://marketingplatform.google.com/about/analytics/',
        ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])->toString(),
      ]),
      '#suffix' => '</div>',
    ];
    // Filter order (tabledrag).
    $form['accounts_wrapper']['accounts'] = [
      '#input' => FALSE,
      '#tree' => TRUE,
      '#type' => 'table',
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'account-order-weight',
        ],
      ],
    ];

    $accounts = $form_state->getValue('accounts', []);
    if ($accounts === []) {
      $entity_accounts = $this->entity->get('tag_container_ids');
      foreach ($entity_accounts as $index => $account) {
        $accounts[$index]['value'] = $account;
        $accounts[$index]['weight'] = $index;
      }
      // Default fallback.
      if (count($accounts) === 0) {
        $accounts[] = ['value' => '', 'weight' => 0];
      }
    }

    foreach ($accounts as $index => $account) {
      $form['accounts_wrapper']['accounts'][$index]['#attributes']['class'][] = 'draggable';
      $form['accounts_wrapper']['accounts'][$index]['#weight'] = $account['weight'];
      $form['accounts_wrapper']['accounts'][$index]['value'] = [
        '#default_value' => (string) ($account['value'] ?? ''),
        '#maxlength' => 20,
        '#required' => (count($accounts) === 1),
        '#size' => 20,
        '#type' => 'textfield',
        '#pattern' => TagContainer::GOOGLE_TAG_MATCH,
        '#ajax' => [
          'callback' => [self::class, 'storeGtagAccountsCallback'],
          'disable-refocus' => TRUE,
          'event' => 'change',
          'wrapper' => 'advanced-settings-wrapper',
        ],
        '#attributes' => [
          'data-disable-refocus' => 'true',
        ],
      ];

      $form['accounts_wrapper']['accounts'][$index]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => (string) ($account['value'] ?? '')]),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $index,
        '#parents' => ['accounts', $index, 'weight'],
        '#attributes' => ['class' => ['account-order-weight']],
      ];

      // If there is more than one id, add the remove button.
      if (count($accounts) > 1) {
        $form['accounts_wrapper']['accounts'][$index]['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_gtag_id_' . $index,
          '#parameter_index' => $index,
          '#limit_validation_errors' => [
            ['accounts'],
          ],
          '#submit' => [
            [self::class, 'removeGtagCallback'],
          ],
          '#ajax' => [
            'callback' => [self::class, 'gtagFormCallback'],
            'wrapper' => $form['#id'],
          ],
        ];
      }
    }

    $id_prefix = implode('-', ['accounts_wrapper', 'accounts']);
    // Add blank account.
    $form['accounts_wrapper']['add_gtag_id'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another ID'),
      '#name' => str_replace('-', '_', $id_prefix) . '_add_gtag_id',
      '#submit' => [
        [self::class, 'addGtagCallback'],
      ],
      '#ajax' => [
        'callback' => [self::class, 'ajaxRefreshAccounts'],
        'wrapper' => $accounts_wrapper_id,
        'effect' => 'fade',
      ],
    ];

    $dimensions_metrics_wrapper_id = Html::getUniqueId('metrics-dimensions-add-more-wrapper');
    $form['dimensions_metrics_wrapper'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Custom dimensions and metrics'),
      '#prefix' => '<div id="' . $dimensions_metrics_wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form['dimensions_metrics_wrapper']['dimensions_metrics'] = [
      '#input' => FALSE,
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => [
        ['data' => $this->t('Type')],
        ['data' => $this->t('Name')],
        ['data' => $this->t('Value')],
        ['data' => $this->t('Remove')],
      ],
      '#empty' => $this->t('No custom dimensions or metrics added.'),
      '#rows' => [],
    ];

    $token_exists = $this->moduleHandler->moduleExists('token');
    $dimensions_metrics = $form_state->getValue('dimensions_metrics', $this->entity->getDimensionsAndMetrics());
    foreach ($dimensions_metrics as $index => $item) {
      $form['dimensions_metrics_wrapper']['dimensions_metrics'][$index]['type'] = [
        '#type' => 'select',
        '#title' => $this->t('Type for parameter #@index', ['@index' => $index]),
        '#title_display' => 'invisible',
        '#options' => [
          'dimension' => $this->t('Dimension'),
          'metric' => $this->t('Metric'),
        ],
        '#default_value' => $item['type'],
        '#disabled' => TRUE,
      ];
      $form['dimensions_metrics_wrapper']['dimensions_metrics'][$index]['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name for parameter #@index', ['@index' => $index]),
        '#title_display' => 'invisible',
        '#maxlength' => 255,
        '#default_value' => $item['name'],
      ];
      $form['dimensions_metrics_wrapper']['dimensions_metrics'][$index]['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Value for parameter #@index', ['@index' => $index]),
        '#title_display' => 'invisible',
        '#maxlength' => 255,
        '#default_value' => $item['value'],
      ];
      if ($token_exists) {
        $form['dimensions_metrics_wrapper']['dimensions_metrics'][$index]['value']['#element_validate'] = [
          'token_element_validate',
        ];
      }
      $form['dimensions_metrics_wrapper']['dimensions_metrics'][$index]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove_parameter' . $index,
        '#parameter_index' => $index,
        '#limit_validation_errors' => [
          ['dimensions_metrics'],
        ],
        '#submit' => [
          [self::class, 'removeDimensionMetric'],
        ],
        '#ajax' => [
          'callback' => [self::class, 'ajaxRefreshMetricsDimensions'],
          'wrapper' => $dimensions_metrics_wrapper_id,
        ],
      ];
    }
    $form['dimensions_metrics_wrapper']['new_dimensions_metrics'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
      'new_metric_dimension_type' => [
        '#type' => 'select',
        '#options' => [
          'dimension' => $this->t('Dimension'),
          'metric' => $this->t('Metric'),
        ],
        '#title' => $this->t('New parameter type'),
        '#title_display' => 'invisible',
        '#empty_option' => t('- Select -'),
        '#empty_value' => '',
      ],
      'new_metric_dimension_submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Add new parameter'),
        '#submit' => [
          [self::class, 'addNewDimensionMetric'],
        ],
        '#ajax' => [
          'callback' => [self::class, 'ajaxRefreshMetricsDimensions'],
          'wrapper' => $dimensions_metrics_wrapper_id,
        ],
      ],
    ];
    if ($token_exists) {
      $form['dimensions_metrics_wrapper']['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['node'],
      ];
    }

    $form['events_settings'] = $this->getEventSettings([], $form_state);

    $form['conditions'] = $this->conditionsForm([], $form_state);

    $form['advanced_settings'] = $this->getAdvancedSettings([], $form_state, $accounts);

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => 'Enabled',
      '#default_value' => $this->entity->status(),
      '#description' => 'Check this checkbox to enable Tag Container.',
    ];

    return $form;
  }

  /**
   * Builds form elements for event plugin configuration.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The augmented form array with the insertion condition elements.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getEventSettings(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['events_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Events'),
      '#description' => $this->t('Events which will be sent to GA.'),
      '#description_display' => 'before',
      '#parents' => ['events_tabs'],
    ];
    $events = $this->entity->get('events');
    $event_definitions = $this->tagEventManager->getDefinitions();

    foreach ($event_definitions as $event_id => $definition) {
      $event_config = $events[$event_id] ?? [];
      $event_plugin = $this->tagEventManager->createInstance($event_id, $event_config);
      $form[$event_id] = [
        '#type' => 'details',
        '#title' => $definition['label'],
        '#group' => 'events_tabs',
      ];
      $form[$event_id]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable event :label', [':label' => $definition['label']]),
        '#default_value' => isset($events[$event_id]) || $this->entity->isNew(),
        '#parameter_index' => $event_id,
      ];
      if ($event_plugin instanceof PluginFormInterface) {
        $form_state->set(['events', $event_id], $event_plugin);
        $form[$event_id]['event_form'] = $event_plugin->buildConfigurationForm([], $form_state);
        $form[$event_id]['event_form']['#type'] = 'fieldset';
        $selector = 'events_settings[' . $event_id . '][enabled]';
        $states = [
          'visible' => [
            ':input[name="' . $selector . '"]' => ['checked' => TRUE],
          ],
        ];
        $form[$event_id]['event_form']['#states'] = $states;
      }
    }
    return $form;
  }

  /**
   * Builds the form elements for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The augmented form array with the insertion condition elements.
   */
  protected function conditionsForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;
    $form['condition_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Conditions'),
      '#description' => $this->t('Conditions in which the tag will be processed on a request.'),
      '#description_display' => 'before',
      '#parents' => ['condition_tabs'],
    ];
    /** @var array<string, array<string, mixed>> $conditions */
    $conditions = $this->entity->get('conditions');
    $definitions = $this->conditionManager->getFilteredDefinitions(
      'google_tag',
      $form_state->getTemporaryValue('gathered_contexts')
    );
    foreach (array_keys($definitions) as $condition_id) {
      // Don't display the current theme condition.
      if ($condition_id === 'current_theme') {
        continue;
      }
      // Don't display the language condition until we have multiple languages.
      if ($condition_id === 'language' && !$this->languageManager->isMultilingual()) {
        continue;
      }

      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionManager->createInstance($condition_id, $conditions[$condition_id] ?? []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'condition_tabs';
      $form[$condition_id] = $condition_form;
    }

    return $form;
  }

  /**
   * Builds form elements for advanced settings configuration.
   *
   * Currently, only Google Tag Manager has advanced settings. However, this
   * form could be used to add Google Tag / GA / etc options as well.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $accounts
   *   A keyed array containing the all the gtag accounts for the container.
   *
   * @return array
   *   The augmented form array with the insertion condition elements.
   */
  protected function getAdvancedSettings(array $form, FormStateInterface $form_state, array $accounts) {
    // Advanced Settings. These are specific settings per ID.
    $advanced_settings = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#description' => $this->t('The settings affecting the snippet contents for this container.'),
      '#attributes' => ['class' => ['google-tag']],
      '#prefix' => '<div id="advanced-settings-wrapper">',
      '#suffix' => '</div>',
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    // Allow disabling of consent options when using non Ads tags.
    $advanced_settings['consent_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce Privacy Consent Policy'),
      '#description' => $this->t("In certain countries and for certain tags (Ads), user consent is required before sending any data. Please review this  <a href='https://www.google.com/about/company/user-consent-policy-help/'>Google FAQ</a> before disabling."),
      '#default_value' => !empty($this->entity->get('advanced_settings')['consent_mode']),
    ];

    // Only show Advanced GTM settings if one of the IDs belong to GTM.
    $gtm_ids = array_slice(
      array_filter(
        $accounts,
        static fn ($id) => preg_match(TagContainer::GOOGLE_TAG_MANAGER_MATCH, $id['value'])
      ),
      0);

    // Add processing / ajax input and append it to the form.
    foreach ($gtm_ids as $gtm_id) {
      $advanced_settings_data = $this->entity->getGtmSettings($gtm_id['value']);
      $advanced_settings['gtm'][$gtm_id['value']] = $this->gtmAdvancedFieldset($advanced_settings_data, $gtm_id['value']);
    }

    return $advanced_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->validateGtmFormValues($form, $form_state);
    $this->validateConditionsForm($form, $form_state);

    $this->buildEntity($form, $form_state);

    $this->validateEventsForm($form, $form_state);
  }

  /**
   * Form validation handler for the event plugins.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateEventsForm(array $form, FormStateInterface $form_state) {
    $events = $form_state->getValue('events_settings');
    $events = array_filter($events, static function (array $event) {
      return (int) $event['enabled'] === 1;
    });
    foreach (array_keys($events) as $event_id) {
      /** @var \Drupal\Core\Plugin\PluginFormInterface $configurable_event */
      if ($configurable_event = $form_state->get(['events', $event_id])) {
        $configurable_event->validateConfigurationForm($form['events_settings'][$event_id]['event_form'], SubformState::createForSubform($form['events_settings'][$event_id]['event_form'], $form, $form_state));
      }
    }
  }

  /**
   * Form submission handler for the event plugins.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function submitEventsForm(array $form, FormStateInterface $form_state) {
    $events = $form_state->getValue('events_settings');
    $events = array_filter($events, static function (array $event) {
      return (int) $event['enabled'] === 1;
    });
    $event_data = [];
    foreach (array_keys($events) as $event_id) {
      if ($event_plugin = $form_state->get(['events', $event_id])) {
        $event_plugin->submitConfigurationForm($form['events_settings'][$event_id]['event_form'], SubformState::createForSubform($form['events_settings'][$event_id]['event_form'], $form, $form_state));
      }
      else {
        /** @var \Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface $event_plugin */
        $event_plugin = $this->tagEventManager->createInstance($event_id);
      }
      $event_data[$event_id] = $event_plugin->getConfiguration();
    }
    $this->entity->set('events', $event_data);
  }

  /**
   * Form validation handler for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateConditionsForm(array $form, FormStateInterface $form_state) {
    // Validate visibility condition settings.
    foreach ($form_state->getValue('conditions') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue(['conditions', $condition_id, 'negate'], (bool) $values['negate']);
      }
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->validateConfigurationForm($form['conditions'][$condition_id], SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $default_id = '';
    // No need to fetch again from entity,
    // ids should already be available in form state values now.
    $tag_container_ids = [];
    foreach ($form_state->getValue('accounts') as $account) {
      if (!$default_id) {
        $default_id = $account['value'];
      }
      $tag_container_ids[$account['weight']] = $account['value'];
    }
    // Need to save tags without weights otherwise it doesn't show up on UI.
    $this->entity->set('tag_container_ids', array_values($tag_container_ids));

    if ($this->entity->id() === NULL) {
      // Set the ID and Label based on the first Google Tag.
      $config_id = uniqid($default_id . '.', TRUE);
      $this->entity->setOriginalId($config_id);
      $this->entity->set('id', $config_id);
      $this->entity->set('label', $default_id);
    }

    $this->submitConditionsForm($form, $form_state);
    $this->submitEventsForm($form, $form_state);

    // Save config entity a first time so that the conditions form can be
    // properly filtered.
    // @see https://www.drupal.org/project/google_tag/issues/3345719#comment-15009415
    // @see BlockForm::submitForm()
    // The fix on https://www.drupal.org/project/google_tag/issues/3357105
    // may not be necessary, but as BlockForm::submitForm() is still
    // doing we are leaving it for now.
    $this->entity->save();

    $this->messenger()->addStatus($this->t('The configuration options have been saved.'));

    // Redirect to collection page.
    $form_state->setRedirect('entity.google_tag_container.single_form');
  }

  /**
   * {@inheritDoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);
    // Store the resulting entity ID in the global Google tag settings.
    $config = $this->configFactory()->getEditable('google_tag.settings');
    $config->set('default_google_tag_entity', $this->entity->id())->save();
    return $result;
  }

  /**
   * Form submission handler for the insertion conditions.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitConditionsForm(array $form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('conditions') as $condition_id => $values) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form['conditions'][$condition_id], SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state));
      $configuration = $condition->getConfiguration();

      // Due to strict type checking, cast negation to a boolean.
      $configuration['negate'] = (bool) (array_key_exists('negate', $configuration) ? $configuration['negate'] : FALSE);

      // Update the insertion conditions on the container.
      $this->entity->getInsertionConditions()->addInstanceId($condition_id, $configuration);
    }
  }

  /**
   * Callback for both ajax account buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public static function gtagFormCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public static function removeGtagCallback(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = $triggering_element['#parameter_index'];
    $accounts = $form_state->getValue('accounts', []);
    unset($accounts[$index]);
    $form_state->setValue('accounts', $accounts);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function addGtagCallback(array &$form, FormStateInterface $form_state) {
    $accounts = $form_state->getValue('accounts', []);
    $accounts[] = [
      'value' => '',
      'weight' => count($accounts),
    ];
    $form_state->setValue('accounts', $accounts);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public static function storeGtagAccountsCallback(array &$form, FormStateInterface $form_state) {
    // Update Advanced Settings Form.
    return $form['advanced_settings'];
  }

  /**
   * Ajax handler for removing dimension metric.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function removeDimensionMetric(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $index = $triggering_element['#parameter_index'];
    $dimensions_metrics = $form_state->getValue('dimensions_metrics', []);
    unset($dimensions_metrics[$index]);
    $form_state->setValue('dimensions_metrics', $dimensions_metrics);
    $form_state->setRebuild();
  }

  /**
   * Ajax handler for adding dimension metric.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function addNewDimensionMetric(array $form, FormStateInterface $form_state) {
    $dimensions_metrics = $form_state->getValue('dimensions_metrics', []);
    $dimensions_metrics[] = [
      'type' => $form_state->getValue('new_metric_dimension_type'),
      'name' => '',
      'value' => '',
    ];
    $form_state->setValue('dimensions_metrics', $dimensions_metrics);
    $form_state->setRebuild();
  }

  /**
   * Ajax handler for refreshing the form metrics wrapper after metrics change.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Metrics wrapper.
   */
  public static function ajaxRefreshMetricsDimensions(array $form, FormStateInterface $form_state) {
    return $form['dimensions_metrics_wrapper'];
  }

  /**
   * Callback for add more gtag accounts.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   Accounts wrapper.
   */
  public static function ajaxRefreshAccounts(array $form, FormStateInterface $form_state) {
    return $form['accounts_wrapper'];
  }

}
