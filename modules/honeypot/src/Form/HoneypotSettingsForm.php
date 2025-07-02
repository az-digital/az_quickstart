<?php

namespace Drupal\honeypot\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configures Honeypot settings for this site.
 */
class HoneypotSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Constructs a HoneypotSettings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'honeypot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['honeypot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Honeypot Configuration.
    $honeypot_config = $this->config('honeypot.settings');
    $form['configuration'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Honeypot Configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['configuration']['protect_all_forms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Protect all forms with Honeypot'),
      '#description' => $this->t('Enable Honeypot protection for ALL forms on this site (it is best to only enable Honeypot for the forms you need below).'),
      '#default_value' => $honeypot_config->get('protect_all_forms'),
    ];
    $form['configuration']['protect_all_forms']['#description'] .= '<br />' . $this->t('<strong>Page caching will be disabled on any page where a form is present if the Honeypot time limit is not set to 0.</strong>');
    $form['configuration']['log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log blocked form submissions'),
      '#description' => $this->t('Log submissions that are blocked due to Honeypot protection.'),
      '#default_value' => $honeypot_config->get('log'),
    ];
    $form['configuration']['element_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Honeypot element name'),
      '#description' => $this->t("The name of the Honeypot form field. It's usually most effective to use a generic name like email, homepage, or link, but this should be changed if it interferes with fields that are already in your forms. Must not contain spaces or special characters. Should not contain words like 'honeypot', as these may allow a spam bot to identify the purpose of this field."),
      '#default_value' => $honeypot_config->get('element_name'),
      '#required' => TRUE,
      '#size' => 30,
    ];
    $form['configuration']['time_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Honeypot time limit'),
      '#description' => $this->t('Minimum time required before a form should be considered entered by a human instead of a bot. Set to 0 to disable.'),
      '#default_value' => $honeypot_config->get('time_limit'),
      '#required' => TRUE,
      '#min' => 0,
      '#size' => 5,
      '#field_suffix' => $this->t('seconds'),
    ];
    $form['configuration']['time_limit']['#description'] .= '<br />' . $this->t('<strong>Page caching will be disabled if there is a form protected by time limit on the page.</strong>');

    $form['configuration']['expire'] = [
      '#type' => 'number',
      '#title' => $this->t('Honeypot expire'),
      '#description' => $this->t("Entries in the {honeypot_user} table that are older than 'expire' seconds will be deleted when cron is run."),
      '#default_value' => $honeypot_config->get('expire'),
      '#required' => TRUE,
      '#min' => 0,
      '#size' => 5,
      '#field_suffix' => $this->t('seconds'),
    ];

    // Honeypot Enabled forms.
    $form_settings = $honeypot_config->get('form_settings');
    $form['form_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Honeypot Enabled Forms'),
      '#description' => $this->t("Check the boxes next to individual forms on which you'd like Honeypot protection enabled."),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
      '#states' => [
        // Hide this fieldset when all forms are protected.
        'invisible' => [
          'input[name="protect_all_forms"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Generic forms.
    $form['form_settings']['general_forms'] = ['#markup' => '<h5>' . $this->t('General Forms') . '</h5>'];
    // User register form.
    $form['form_settings']['user_register_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User Registration form'),
      '#default_value' => $this->getFormSettingsValue($form_settings, 'user_register_form'),
    ];
    // User password form.
    $form['form_settings']['user_pass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('User Password Reset form'),
      '#default_value' => $this->getFormSettingsValue($form_settings, 'user_pass'),
    ];

    // If contact.module enabled, add contact forms.
    if ($this->moduleHandler->moduleExists('contact')) {
      $form['form_settings']['contact_forms'] = ['#markup' => '<h5>' . $this->t('Contact Forms') . '</h5>'];

      $bundles = $this->entityTypeBundleInfo->getBundleInfo('contact_message');
      $formController = $this->entityTypeManager->getFormObject('contact_message', 'default');

      foreach ($bundles as $bundle_key => $bundle) {
        $stub = $this->entityTypeManager->getStorage('contact_message')->create([
          'contact_form' => $bundle_key,
        ]);
        $formController->setEntity($stub);
        $form_id = $formController->getFormId();

        $form['form_settings'][$form_id] = [
          '#type' => 'checkbox',
          '#title' => Html::escape($bundle['label']),
          '#default_value' => $this->getFormSettingsValue($form_settings, $form_id),
        ];
      }
    }

    // Node types for node forms.
    if ($this->moduleHandler->moduleExists('node')) {
      /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface[] $types */
      $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      if (!empty($types)) {
        // Node forms.
        $form['form_settings']['node_forms'] = ['#markup' => '<h5>' . $this->t('Node Forms') . '</h5>'];
        foreach ($types as $type) {
          $id = 'node_' . $type->get('type') . '_form';
          $form['form_settings'][$id] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@name node form', ['@name' => $type->label()]),
            '#default_value' => $this->getFormSettingsValue($form_settings, $id),
          ];
        }
      }
    }

    // Comment types for comment forms.
    if ($this->moduleHandler->moduleExists('comment')) {
      $types = $this->entityTypeManager->getStorage('comment_type')->loadMultiple();
      if (!empty($types)) {
        $form['form_settings']['comment_forms'] = ['#markup' => '<h5>' . $this->t('Comment Forms') . '</h5>'];
        foreach ($types as $type) {
          $id = 'comment_' . $type->id() . '_form';
          $form['form_settings'][$id] = [
            '#type' => 'checkbox',
            '#title' => $this->t('@name comment form', ['@name' => $type->label()]),
            '#default_value' => $this->getFormSettingsValue($form_settings, $id),
          ];
        }
      }
    }

    // Store the keys we want to save in configuration when form is submitted.
    $keys_to_save = array_keys($form['configuration']);
    foreach ($keys_to_save as $key => $key_to_save) {
      if (strpos($key_to_save, '#') !== FALSE) {
        unset($keys_to_save[$key]);
      }
    }
    $form_state->setStorage(['keys' => $keys_to_save]);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Make sure Honeypot element name only contains A-Z, 0-9.
    if (!preg_match("/^[-_a-zA-Z0-9]+$/", $form_state->getValue('element_name'))) {
      $form_state->setErrorByName('element_name', $this->t('The element name cannot contain spaces or other special characters.'));
    }

    // Make sure Honeypot element name starts with a letter.
    if (!preg_match("/^[a-zA-Z].+$/", $form_state->getValue('element_name'))) {
      $form_state->setErrorByName('element_name', $this->t('The element name must start with a letter.'));
    }

    // Make sure Honeypot element name isn't one of the reserved names.
    $reserved_element_names = [
      'name',
      'pass',
      'website',
    ];
    if (in_array($form_state->getValue('element_name'), $reserved_element_names)) {
      $form_state->setErrorByName('element_name', $this->t('The element name cannot match one of the common Drupal form field names (e.g. @names).', ['@names' => implode(', ', $reserved_element_names)]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('honeypot.settings');
    $storage = $form_state->getStorage();

    // Save all the Honeypot configuration items from $form_state.
    foreach ($form_state->getValues() as $key => $value) {
      if (in_array($key, $storage['keys'])) {
        $config->set($key, $value);
      }
    }

    // Save the honeypot forms from $form_state into a 'form_settings' array.
    $config->set('form_settings', $form_state->getValue('form_settings'))->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Gets a value from the retrieved form settings array.
   *
   * @param array $form_settings
   *   Array of configuration settings that Honeypot uses to determine which
   *   forms to protect. Keys are form IDs, values are boolean to indicate
   *   whether that form ID should be protected.
   * @param string $form_id
   *   The Form ID.
   *
   * @return bool
   *   TRUE if the form is protected.
   */
  protected function getFormSettingsValue(array $form_settings, string $form_id) {
    // If there are settings in the array and the form ID already has a setting,
    // return the saved setting for the form ID.
    if (!empty($form_settings) && isset($form_settings[$form_id])) {
      return $form_settings[$form_id];
    }
    // Default to false.
    else {
      return FALSE;
    }
  }

}
