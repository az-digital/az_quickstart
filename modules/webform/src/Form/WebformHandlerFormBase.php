<?php

namespace Drupal\webform\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\Element\MachineName;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base webform for webform handlers.
 */
abstract class WebformHandlerFormBase extends FormBase {

  use WebformDialogFormTrait;

  /**
   * Machine name maxlength.
   */
  const MACHINE_NAME_MAXLENGTH = 64;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The transliteration helper.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform handler.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_handler_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->languageManager = $container->get('language_manager');
    $instance->transliteration = $container->get('transliteration');
    $instance->tokenManager = $container->get('webform.token_manager');
    return $instance;
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param string $webform_handler
   *   The webform handler ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws not found exception if the number of handler instances for this
   *   webform exceeds the handler's cardinality.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $this->webform = $webform;
    try {
      $this->webformHandler = $this->prepareWebformHandler($webform_handler);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid handler id: '$webform_handler'.");
    }

    // Limit the number of plugin instanced allowed.
    if (!$this->webformHandler->getHandlerId()) {
      $plugin_id = $this->webformHandler->getPluginId();
      $cardinality = $this->webformHandler->cardinality();
      $number_of_instances = $webform->getHandlers($plugin_id)->count();
      if ($cardinality !== WebformHandlerInterface::CARDINALITY_UNLIMITED && $cardinality <= $number_of_instances) {
        throw new NotFoundHttpException(
          $this->formatPlural(
            $cardinality,
            'Only @count instance is permitted',
            'Only @count instances are permitted'
          )
        );
      }
    }

    // Add meta data to webform handler form.
    // This information makes it a little easier to alter a handler's form.
    $form['#webform_id'] = $this->webform->id();
    $form['#webform_handler_id'] = $this->webformHandler->getHandlerId();
    $form['#webform_handler_plugin_id'] = $this->webformHandler->getPluginId();

    $request = $this->getRequest();

    $form['description'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => $this->webformHandler->description(),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
      '#weight' => -20,
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->webformHandler->getPluginId(),
    ];

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#weight' => -10,
    ];
    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->webformHandler->label(),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['general']['handler_id'] = [
      '#type' => 'machine_name',
      '#maxlength' => static::MACHINE_NAME_MAXLENGTH,
      '#description' => $this->t('A unique name for this handler instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => $this->webformHandler->getHandlerId() ?: NULL,
      '#required' => TRUE,
      '#disabled' => $this->webformHandler->getHandlerId() ? TRUE : FALSE,
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'exists'],
      ],
      '#element_validate' => [
        [$this, 'validateMachineName'],
        [MachineName::class, 'validateMachineName'],
      ],
    ];
    $form['general']['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Administrative notes'),
      '#description' => $this->t("Entered text will be displayed on the handlers administrative page and replace this handler's default description."),
      '#rows' => 2,
      '#default_value' => $this->webformHandler->getNotes(),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#weight' => -10,
    ];
    $form['advanced']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the %name handler.', ['%name' => $this->webformHandler->label()]),
      '#return_value' => TRUE,
      '#default_value' => $this->webformHandler->isEnabled(),
      // Disable broken plugins.
      '#disabled' => ($this->webformHandler->getPluginId() === 'broken'),
    ];

    $form['#parents'] = [];
    $form['settings'] = [
      '#tree' => TRUE,
      '#parents' => ['settings'],
    ];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->webformHandler->buildConfigurationForm($form['settings'], $subform_state);

    // Get $form['settings']['#attributes']['novalidate'] and apply it to the
    // $form.
    // This allows handlers with hide/show logic to skip HTML5 validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    if (isset($form['settings']['#attributes']['novalidate'])) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }
    $form['settings']['#tree'] = TRUE;

    // Conditional logic.
    if ($this->webformHandler->supportsConditions()) {
      $form['conditional_logic'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Conditional logic'),
      ];
      $form['conditional_logic']['conditions'] = [
        '#type' => 'webform_element_states',
        '#state_options' => [
          'enabled' => $this->t('Enabled'),
          'disabled' => $this->t('Disabled'),
        ],
        '#selector_options' => $webform->getElementsSelectorOptions(['excluded_elements' => []]),
        '#selector_sources' => $webform->getElementsSelectorSourceValues(),
        '#multiple' => FALSE,
        '#default_value' => $this->webformHandler->getConditions(),
      ];
    }

    // Check the URL for a weight, then the webform handler,
    // otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->webformHandler->getWeight(),
    ];

    // Build tabs.
    $tabs = [
      'conditions' => [
        'title' => $this->t('Conditions'),
        'elements' => [
          'conditional_logic',
        ],
        'weight' => 10,
      ],
      'advanced' => [
        'title' => $this->t('Advanced'),
        'elements' => [
          'advanced',
          'additional',
          'development',
        ],
        'weight' => 20,
      ],
    ];
    $form = WebformFormHelper::buildTabs($form, $tabs);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Add token links below the form and on every tab.
    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();
    if ($form['token_tree_link']) {
      $form['token_tree_link'] += [
        '#weight' => 101,
      ];
    }
    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The webform handler configuration is stored in the 'settings' key in
    // the webform, pass that through for validation.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->webformHandler->validateConfigurationForm($form, $subform_state);

    // Process handler state webform errors.
    $this->processHandlerFormErrors($subform_state, $form_state);

    // Update the original webform values.
    $form_state->setValue('settings', $subform_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The webform handler configuration is stored in the 'settings' key in
    // the webform, pass that through for submission.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->webformHandler->submitConfigurationForm($form, $subform_state);

    // Update the original webform values.
    $form_state->setValue('settings', $subform_state->getValues());

    $this->webformHandler->setHandlerId($form_state->getValue('handler_id'));
    $this->webformHandler->setLabel($form_state->getValue('label'));
    $this->webformHandler->setNotes($form_state->getValue('notes'));
    $this->webformHandler->setStatus($form_state->getValue('status'));
    $this->webformHandler->setWeight($form_state->getValue('weight'));
    $this->webformHandler->setConditions($form_state->getValue('conditions') ?? []);

    if ($this instanceof WebformHandlerAddForm) {
      $this->webform->addWebformHandler($this->webformHandler);
      $this->messenger()->addStatus($this->t('The webform handler was successfully added.'));
    }
    else {
      $this->webform->updateWebformHandler($this->webformHandler);
      $this->messenger()->addStatus($this->t('The webform handler was successfully updated.'));
    }

    $form_state->setRedirectUrl($this->webform->toUrl('handlers', ['query' => ['update' => $this->webformHandler->getHandlerId()]]));
  }

  /**
   * Validates the machine name for a webform handler instance.
   *
   * This method verifies the uniqueness of the machine name and updates the
   * machine name with a count suffix if another handler with the same machine
   * name already exists.
   *
   * @see \Drupal\Core\Render\Element\MachineName::validateMachineName()
   */
  public function validateMachineName(&$element, FormStateInterface $form_state, &$complete_form) {
    // If the machine name matches the default machine name, it does not need to
    // be validated (i.e. during handler edit form save).
    if (isset($element['#default_value']) && $element['#default_value'] === $element['#value']) {
      return;
    }

    $count = 1;
    $machine_name = $element['#value'];
    $instance_ids = $this->webform->getHandlers()->getInstanceIds();
    while (isset($instance_ids[$machine_name])) {
      $machine_name = $element['#value'] . '_' . $count++;
    }
    $element['#value'] = $machine_name;
    $form_state->setValueForElement($element, $machine_name);
  }

  /**
   * Determines if the webform handler ID already exists.
   *
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return bool
   *   TRUE if the webform handler ID exists, FALSE otherwise.
   */
  public function exists($handler_id) {
    $instance_ids = $this->webform->getHandlers()->getInstanceIds();
    return (isset($instance_ids[$handler_id])) ? TRUE : FALSE;
  }

  /**
   * Get the webform handler's webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Get the webform handler.
   *
   * @return \Drupal\webform\Plugin\WebformHandlerInterface
   *   A webform handler.
   */
  public function getWebformHandler() {
    return $this->webformHandler;
  }

  /**
   * Process handler webform errors in webform.
   *
   * @param \Drupal\Core\Form\FormStateInterface $handler_state
   *   The webform handler webform state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The webform state.
   */
  protected function processHandlerFormErrors(FormStateInterface $handler_state, FormStateInterface &$form_state) {
    foreach ($handler_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

}
