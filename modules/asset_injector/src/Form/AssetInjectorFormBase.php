<?php

namespace Drupal\asset_injector\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for duplicating JavaScript asset injector configurations.
 *
 * This form is used to create a duplicate of an existing JavaScript asset
 * injector configuration. The duplicated configuration will have the same
 * settings as the original but with a new label.
 *
 * @package Drupal\asset_injector\Form
 */
class AssetInjectorFormBase extends EntityForm {

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The Asset entity.
   *
   * @var \Drupal\asset_injector\AssetInjectorInterface
   */
  protected $entity;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $language;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   */
  protected $themeHandler;

  /**
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('logger.factory')->get('asset_injector'),
      $container->get('plugin.manager.condition'),
      $container->get('context.repository'),
      $container->get('language_manager'),
      $container->get('theme_handler'),
      $container->get('plugin_form.factory')
    );
  }

  /**
   * AssetInjectorFormBase constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $manager
   *   The ConditionManager for building the conditions UI.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The lazy context repository service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language
   *   The language manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   */
  public function __construct(LoggerInterface $logger, ExecutableManagerInterface $manager, ContextRepositoryInterface $context_repository, LanguageManagerInterface $language, ThemeHandlerInterface $theme_handler, PluginFormFactoryInterface $plugin_form_manager) {
    $this->logger = $logger;
    $this->manager = $manager;
    $this->contextRepository = $context_repository;
    $this->language = $language;
    $this->themeHandler = $theme_handler;
    $this->pluginFormFactory = $plugin_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#tree'] = TRUE;
    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    /** @var \Drupal\asset_injector\Entity\AssetInjectorBase $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t('Label for the @type.', [
        '@type' => $entity->getEntityType()->getLabel(),
      ]),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\\' . $entity->getEntityType()->getClass() . '::load',
        'replace_pattern' => '[^a-z0-9_]+',
        'replace' => '_',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $this->entity->status(),
    ];

    $form['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code'),
      '#description' => $this->t('The actual code goes in here.'),
      '#rows' => 10,
      '#default_value' => $entity->code,
      '#required' => TRUE,
      '#prefix' => '<div>',
      '#suffix' => '<div class="resizable"><div class="ace-editor"></div></div></div>',
    ];

    $form['conditions'] = $this->buildConditionsInterface([], $form_state);
    $form['conditions']['#weight'] = 99;

    $form['conditions_and_or'] = [
      '#type' => 'details',
      '#title' => $this->t('Condition Requirements'),
      '#group' => 'conditions_tabs',
      '#weight' => 999,
      '#tree' => FALSE,
    ];

    $form['conditions_and_or']['conditions_require_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require all conditions'),
      '#description' => $this->t('Check to require all conditions. Leave uncheck to require any condition.'),
      '#default_value' => $entity->conditions_require_all,
    ];

    $form['#attached']['library'][] = 'asset_injector/ace-editor';
    return $form;
  }

  /**
   * Helper function for building the conditions UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the conditions UI added in.
   */
  protected function buildConditionsInterface(array $form, FormStateInterface $form_state) {
    $form['conditions_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Conditions'),
      '#parents' => ['conditions_tabs'],
      '#attached' => [
        'library' => [
          'asset_injector/asset_injector',
        ],
      ],
    ];

    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $conditions = $this->entity->getConditions();
    foreach ($this->manager->getDefinitionsForContexts($form_state->getTemporaryValue('gathered_contexts')) as $condition_id => $definition) {
      // Don't display the language condition until we have multiple languages.
      if ($condition_id == 'language' && !$this->language->isMultilingual()) {
        continue;
      }

      $condition_config = $conditions[$condition_id] ?? [];
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->manager->createInstance($condition_id, $condition_config);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'conditions_tabs';

      if ($condition_id == 'current_theme') {
        $condition_form['theme']['#empty_option'] = $this->t('- None -');
        // Drupal 9.5+ added scheme for the current_theme condition plugin. This
        // broke the ability to set an asset with more than 1 theme. To avoid
        // the need for database updates, just present the user with some info
        // and put the action on them.
        if (isset($condition_config['theme']) && is_array($condition_config['theme']) && count($condition_config['theme']) >= 2) {
          $this->messenger()
            ->addWarning($this->t('Theme conditions is now only limited to a single theme per asset. The currently configured themes of %themes will be limited to only 1 theme upon saving. Please review the theme condition settings prior to saving. See the <a href="https://www.drupal.org/project/asset_injector/issues/3329577">Drupal.org issue</a> for more detailed information.', ['%themes' => implode(', ', $condition_config['theme'])]));
        }
      }

      $form[$condition_id] = $condition_form;
    }

    // Modify the titles of the node_type plugin & hide negate.
    if (isset($form['node_type'])) {
      $form['node_type']['#title'] = $this->t('Content types');
      $form['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['node_type']['negate']['#type'] = 'hidden';
      $form['node_type']['negate']['#value'] = $form['node_type']['negate']['#default_value'];
    }

    // Modify the request_path negate to a radio button.
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    $element['saveContinue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and Continue Editing'),
      '#name' => 'save_continue',
      '#submit' => ['::submitForm', '::save'],
      '#weight' => 7,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $conditions = $form_state->getValue('conditions');
    // Validate conditions condition settings.
    foreach ($conditions as $condition_id => &$values) {

      // Since core theme condition doesn't support multiple theme choices, if
      // no themes are selected, we change it to an empty string so that the
      // plugin validation and submit will function as expected.
      if ($condition_id == 'current_theme' && empty($values['theme'])) {
        $values['theme'] = '';
        $form_state->setValue('conditions', $conditions);
      }

      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $form_state->setValue([
          'conditions',
          $condition_id,
          'negate',
        ], (bool) $values['negate']);
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);

      // Fix some issues with webform & context with their entity_bundle
      // conditions. Even with the array empty, the condition still saves
      // with values. This produces logic issues when resolving the conditions.
      // @see https://www.drupal.org/node/2857279
      $values = $form_state->getValue(['conditions', $condition_id]);
      foreach ($values as &$value) {
        if (is_array($value)) {
          $value = array_filter($value);
        }
      }
      $form_state->setValue(['conditions', $condition_id], $values);

      $condition->validateConfigurationForm($form['conditions'][$condition_id], SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state));
    }

    // Check for unexpected leading and/or trailing tags in the code field.
    $rejectedTags = ['script', 'style'];
    $codeValue = trim($form_state->getValue('code'));

    // In this case we cannot simply call contains() for the check,
    // as some JS code may have these tags inside of strings, which
    // should not be removed.
    foreach ($rejectedTags as $rejectedTag) {
      if (str_starts_with($codeValue, '<' . $rejectedTag . '>') || str_ends_with($codeValue, '</' . $rejectedTag . '>')) {
        $form_state->setErrorByName('code', $this->t('There must be no leading or trailing @tag_name tags.', ['@tag_name' => '<' . $rejectedTag . '>']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Convert \r\n to \n so that multiline strings are properly formatted.
    // @see \Symfony\Component\Yaml\Dumper::dump
    $code = $form_state->getValue('code');
    $code = preg_replace('~\r\n?~', "\n", $code);
    $form_state->setValue('code', $code);

    parent::submitForm($form, $form_state);

    foreach ($form_state->getValue('conditions') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition->submitConfigurationForm($form['conditions'][$condition_id], SubformState::createForSubform($form['conditions'][$condition_id], $form, $form_state));
      $condition_configuration = $condition->getConfiguration();
      // Update the conditions on the asset.
      $this->entity->getConditionsCollection()
        ->addInstanceId($condition_id, $condition_configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = $entity->save();

    switch ($status) {
      case SAVED_NEW:
        $message = $this->t('Created the %label Asset Injector.', [
          '%label' => $entity->label(),
        ]);
        $log = '%type asset %id created';
        break;

      default:
        $message = $this->t('Saved the %label Asset Injector.', [
          '%label' => $entity->label(),
        ]);
        $log = '%type asset %id saved';
    }
    $this->messenger()->addMessage($message);
    $this->logger->notice($log, [
      '%type' => $entity->getEntityTypeId(),
      '%id' => $entity->id(),
    ]);

    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && $trigger['#name'] != 'save_continue') {
      $form_state->setRedirectUrl($entity->toUrl('collection'));
    }
    else {
      $form_state->setRedirectUrl($entity->toUrl());
    }
    return $status;
  }

}
