<?php

namespace Drupal\field_group\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_group\FieldGroupFormatterPluginManager;
use Drupal\field_group\FieldgroupUi;
use Drupal\field_group\FormatterHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for adding a fieldgroup to a bundle.
 */
class FieldGroupAddForm extends FormBase {

  /**
   * The prefix for groups.
   *
   * @var string
   */
  const GROUP_PREFIX = 'group_';

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The context for the group.
   *
   * @var string
   */
  protected $context;

  /**
   * The mode for the group.
   *
   * @var string
   */
  protected $mode;

  /**
   * Current step of the form.
   *
   * @var string
   */
  protected $currentStep;

  /**
   * The field group formatter plugin manager.
   *
   * @var \Drupal\field_group\FieldGroupFormatterPluginManager
   */
  protected $fieldGroupFormatterPluginManager;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The cache backend service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * FieldGroupAddForm constructor.
   *
   * @param \Drupal\field_group\FieldGroupFormatterPluginManager $fieldGroupFormatterPluginManager
   *   The field group formatter plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The cache backend service.
   */
  public function __construct(FieldGroupFormatterPluginManager $fieldGroupFormatterPluginManager, MessengerInterface $messenger, CacheBackendInterface $cacheBackend) {
    $this->fieldGroupFormatterPluginManager = $fieldGroupFormatterPluginManager;
    $this->messenger = $messenger;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field_group.formatters'),
      $container->get('messenger'),
      $container->get('cache.default')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_group_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL, $context = NULL) {

    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $this->context = $context;

    if ($context == 'form') {
      $this->mode = $this->getRequest()->get('form_mode_name');
    }
    else {
      $this->mode = $this->getRequest()->get('view_mode_name');
    }

    if (empty($this->mode)) {
      $this->mode = 'default';
    }

    if (!$form_state->get('step')) {
      $form_state->set('step', 'formatter');
    }
    $this->currentStep = $form_state->get('step');

    if ($this->currentStep == 'formatter') {
      $this->buildFormatterSelectionForm($form, $form_state);
    }
    else {
      $this->buildConfigurationForm($form, $form_state);
    }

    return $form;
  }

  /**
   * Build the formatter selection step.
   */
  public function buildFormatterSelectionForm(array &$form, FormStateInterface $form_state) {

    // Gather group formatters.
    $formatter_options = FormatterHelper::formatterOptions($this->context);
    $form['add'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline', 'clearfix']],
    ];

    $form['add']['group_formatter'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a new group'),
      '#options' => $formatter_options,
      '#empty_option' => $this->t('- Select a field group type -'),
      '#required' => TRUE,
    ];

    // Field label and field_name.
    $form['new_group_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        '!visible' => [
          ':input[name="group_formatter"]' => ['value' => ''],
        ],
      ],
    ];
    $form['new_group_wrapper']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#size' => 15,
      '#required' => TRUE,
    ];

    $form['new_group_wrapper']['group_name'] = [
      '#type' => 'machine_name',
      '#size' => 15,
      // This field should stay LTR even for RTL languages.
      '#field_prefix' => '<span dir="ltr">' . self::GROUP_PREFIX,
      '#field_suffix' => '</span>&lrm;',
      '#description' => $this->t('A unique machine-readable name containing letters, numbers, and underscores.'),
      '#maxlength' => FieldStorageConfig::NAME_MAX_LENGTH - strlen(self::GROUP_PREFIX),
      '#machine_name' => [
        'source' => ['new_group_wrapper', 'label'],
        'exists' => [$this, 'groupNameExists'],
      ],
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#validate' => [
        [$this, 'validateFormatterSelection'],
      ],
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
  }

  /**
   * Build the formatter configuration form.
   */
  public function buildConfigurationForm(array &$form, FormStateInterface $form_state) {

    $group = new \stdClass();
    $group->context = $this->context;
    $group->entity_type = $this->entityTypeId;
    $group->bundle = $this->bundle;
    $group->mode = $this->mode;

    $manager = $this->fieldGroupFormatterPluginManager;
    $plugin = $manager->getInstance([
      'format_type' => $form_state->getValue('group_formatter'),
      'configuration' => [
        'label' => $form_state->getValue('label'),
      ],
      'group' => $group,
    ]);

    $form['format_settings'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    $form['format_settings'] += $plugin->settingsForm();

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create group'),
      '#button_type' => 'primary',
    ];

  }

  /**
   * Validate the formatter selection step.
   */
  public function validateFormatterSelection(array &$form, FormStateInterface $form_state) {

    $group_name = self::GROUP_PREFIX . $form_state->getValue('group_name');

    // Add the prefix.
    $form_state->setValueForElement($form['new_group_wrapper']['group_name'], $group_name);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->get('step') == 'formatter') {
      $form_state->set('step', 'configuration');
      $form_state->set('group_label', $form_state->getValue('label'));
      $form_state->set('group_name', $form_state->getValue('group_name'));
      $form_state->set('group_formatter', $form_state->getValue('group_formatter'));
      $form_state->setRebuild();
    }
    else {

      $new_group = (object) [
        'group_name' => $form_state->get('group_name'),
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'mode' => $this->mode,
        'context' => $this->context,
        'children' => [],
        'parent_name' => '',
        'weight' => 20,
        'format_type' => $form_state->get('group_formatter'),
        'region' => 'hidden',
      ];

      $new_group->format_settings = $form_state->getValue('format_settings');
      $new_group->label = $new_group->format_settings['label'];
      unset($new_group->format_settings['label']);
      $new_group->format_settings += $this->fieldGroupFormatterPluginManager->getDefaultSettings($form_state->get('group_formatter'), $this->context);

      field_group_group_save($new_group);

      // Store new group information for any additional submit handlers.
      $groups_added = $form_state->get('groups_added');
      $groups_added['_add_new_group'] = $new_group->group_name;
      $this->messenger->addMessage($this->t('New group %label successfully created.', ['%label' => $new_group->label]));

      $form_state->setRedirectUrl(FieldgroupUi::getFieldUiRoute($new_group));
      $this->cacheBackend->invalidate('field_groups');

    }

  }

  /**
   * Checks if a group machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'group_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the group machine name is taken.
   */
  public function groupNameExists($value, array $element, FormStateInterface $form_state) {

    // Add the prefix.
    $group_name = self::GROUP_PREFIX . $value;

    return field_group_exists($group_name, $this->entityTypeId, $this->bundle, $this->context, $this->mode);
  }

}
