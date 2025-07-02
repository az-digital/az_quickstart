<?php

namespace Drupal\metatag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\metatag\MetatagSeparator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration export form.
 */
class MetatagSettingsForm extends ConfigFormBase {

  use MetatagSeparator;

  /**
   * The metatag.manager service.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The entity_type.bundle.info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The tag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagPluginManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /**
     * @var \Drupal\metatag\Form\MetatagSettingsForm
     */
    $instance = parent::create($container);
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->metatagManager = $container->get('metatag.manager');
    $instance->state = $container->get('state');
    $instance->tagPluginManager = $container->get('plugin.manager.metatag.tag');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'metatag_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['metatag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->state->get('system.maintenance_mode')) {
      $this->messenger()->addMessage($this->t('Please note that while the site is in maintenance mode none of the usual meta tags will be output.'));
    }
    $settings = $this->config('metatag.settings');
    $entity_type_groups = $settings->get('entity_type_groups');

    $separator = $settings->get('separator');
    if (is_null($separator) || $separator == '') {
      $separator = $this::$defaultSeparator;
    }
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator used with multiple values'),
      '#description' => $this->t('Controls the separator used when a meta tag allows multiple values. Multiple characters can be used together, it does not have to be one single character long. Defaults to ":default".', [
        ':default' => $this::$defaultSeparator,
      ]),
      '#size' => 10,
      '#default_value' => trim($separator),
      '#required' => TRUE,
    ];

    $form['entity_type_groups'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Entity type / Group Mapping'),
      '#description' => $this->t('Identify which metatag groups should be available on which entity type / bundle combination. Unselected groups will not appear on the configuration form for that entity type, reducing the size of the form and increasing performance. If no groups are selected for a type, all groups will appear.'),
      '#tree' => TRUE,
    ];

    $metatag_groups = $this->metatagManager->sortedGroups();
    $entity_types = MetatagDefaultsForm::getSupportedEntityTypes();
    foreach ($entity_types as $entity_type => $entity_label) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type);
      foreach ($bundles as $bundle_id => $bundle_info) {
        // Create an option list for each bundle.
        $options = [];
        foreach ($metatag_groups as $group_name => $group_info) {
          $options[$group_name] = $group_info['label'];
        }
        // Format a collapsible fieldset for each group for easier readability.
        $form['entity_type_groups'][$entity_type][$bundle_id] = [
          '#type' => 'details',
          '#title' => $entity_label . ': ' . $bundle_info['label'],
        ];
        $form['entity_type_groups'][$entity_type][$bundle_id][] = [
          '#type' => 'checkboxes',
          '#options' => $options,
          '#default_value' => isset($entity_type_groups[$entity_type]) && isset($entity_type_groups[$entity_type][$bundle_id]) ? $entity_type_groups[$entity_type][$bundle_id] : [],
        ];
      }
    }

    $trimSettingsMaxlength = $this->config('metatag.settings')->get('tag_trim_maxlength');
    $trimMethod = $this->config('metatag.settings')->get('tag_trim_method');

    $form['tag_trim'] = [
      '#title' => $this->t('Metatag Trimming Options'),
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#description' => $this->t("Many Meta-Tags can be trimmed on a specific length for search engine optimization.<br/>If the value is set to '0' or left empty, the whole Metatag will be untrimmed."),
    ];

    // Optional support for the Maxlenth module.
    $form['tag_trim']['use_maxlength'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Maxlength module to force these limits?'),
      '#default_value' => $this->config('metatag.settings')->get('use_maxlength') ?? TRUE,
      '#description' => $this->t('Because of how tokens are processed in meta tags, use of the Maxlength module may not provide an accurate representation of the actual current length of each meta tag, so it may cause more problem than it is worth.'),
    ];
    if (!$this->moduleHandler->moduleExists('maxlength')) {
      $form['tag_trim']['use_maxlength']['#disabled'] = TRUE;
      $form['tag_trim']['use_maxlength']['#description'] = $this->t('Install the Maxlength module to enable this option.');
    }

    $form['tag_trim']['maxlength'] = [
      '#title' => $this->t('Tags'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    // Name the variable "metatag_id" to avoid confusing this with the "name"
    // value from the meta tag plugin as it's actually the plugin ID.
    foreach ($this->metatagManager->sortedTags() as $metatag_id => $metatag_info) {
      if (!empty($metatag_info['trimmable'])) {
        $form['tag_trim']['maxlength']['metatag_maxlength_' . $metatag_id] = [
          '#title' => $this->t('Meta Tags:') . ' ' . $metatag_id . ' ' . $this->t('length'),
          '#type' => 'number',
          '#required' => FALSE,
          '#default_value' => $trimSettingsMaxlength['metatag_maxlength_' . $metatag_id] ?? NULL,
          '#min' => 0,
          '#step' => 1,
        ];
      }
    }

    $form['tag_trim']['tag_trim_method'] = [
      '#title' => $this->t('Trimming options'),
      '#type' => 'select',
      '#required' => TRUE,
      '#default_value' => $trimMethod ?? 'beforeValue',
      '#options' => [
        'afterValue' => $this->t('Trim the Meta Tag after the word on the given value'),
        'onValue' => $this->t('Trim the Meta Tag on the given value'),
        'beforeValue' => $this->t('Trim the Meta Tag before the word on the given value'),
      ],
    ];

    $form['tag_trim']['tag_trim_end'] = [
      '#title' => $this->t('Characters to trim'),
      '#type' => 'textfield',
      '#default_value' => $this->config('metatag.settings')->get('tag_trim_end'),
      '#description' => $this->t('A list of characters to trim at the end of all metatags. Provide a single string without any separators, e.g. "|,." (instead of "| , ."). Note that spaces, tabs, new lines, carriage returns and vertical tabs (" \n\r\t\v") will be trimmed automatically and do not need to be listed in this field. The trimming is applied at the very end after the tag is trimmed for length, and after the trimming option was executed.'),
    ];

    $scrollheight = $this->config('metatag.settings')->get('tag_scroll_max_height');

    $form['firehose_widget'] = [
      '#title' => $this->t('Metatag widget options'),
      '#type' => 'details',
      '#tree' => TRUE,
      '#open' => TRUE,
      '#description' => $this->t("Various options for the field widget used on entity forms, e.g. on content type forms."),
    ];

    $form['firehose_widget']['tag_scroll_max_height'] = [
      '#title' => $this->t('Scroll maximum height'),
      '#type' => 'textfield',
      '#default_value' => $scrollheight,
      '#placeholder' => $this->t('eg 500px or 8rem'),
      '#description' => $this->t("To enable scrolling please enter a value and its units, e.g. 500px, 8rem, etc. Removing this value will remove the scroll."),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $settings = $this->config('metatag.settings');
    // entity_type_groups handling:
    $entityTypeGroupsValues = $form_state->getValue('entity_type_groups');
    $entityTypeGroupsValues = static::arrayFilterRecursive($entityTypeGroupsValues);
    // Remove the extra layer created by collapsible fieldsets.
    foreach ($entityTypeGroupsValues as $entity_type => $bundle) {
      foreach ($bundle as $bundle_id => $groups) {
        $entityTypeGroupsValues[$entity_type][$bundle_id] = $groups[0];
      }
    }
    $settings->set('entity_type_groups', $entityTypeGroupsValues);

    $settings->set('separator', trim($form_state->getValue('separator')));

    // tag_trim handling:
    $use_maxlength = $form_state->getValue(['tag_trim', 'use_maxlength']);
    $settings->set('use_maxlength', $use_maxlength);
    $trimmingMethod = $form_state->getValue(['tag_trim', 'tag_trim_method']);
    $settings->set('tag_trim_method', $trimmingMethod);
    $trimmingValues = $form_state->getValue(['tag_trim', 'maxlength']);
    $settings->set('tag_trim_maxlength', $trimmingValues);
    $trimEndCharacters = $form_state->getValue(['tag_trim', 'tag_trim_end']);
    $settings->set('tag_trim_end', $trimEndCharacters);

    // Widget settings.
    $scrollheightvalue = $form_state->getValue([
      'firehose_widget',
      'tag_scroll_max_height',
    ]);
    $settings->set('tag_scroll_max_height', $scrollheightvalue);

    $settings->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Recursively filter results.
   *
   * @param array $input
   *   The array to filter.
   *
   * @return array
   *   The filtered array.
   */
  public static function arrayFilterRecursive(array $input): array {
    foreach ($input as &$value) {
      if (is_array($value)) {
        $value = static::arrayFilterRecursive($value);
      }
    }
    return array_filter($input);
  }

}
