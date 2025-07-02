<?php

namespace Drupal\block_field\Plugin\Field\FieldType;

use Drupal\block_field\BlockFieldItemInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'block_field' field type.
 *
 * @FieldType(
 *   id = "block_field",
 *   label = @Translation("Block (plugin)"),
 *   description = @Translation("Stores an instance of a configurable or custom block."),
 *   category = "reference",
 *   default_widget = "block_field_default",
 *   default_formatter = "block_field",
 * )
 */
class BlockFieldItem extends FieldItemBase implements BlockFieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'selection' => 'blocks',
      'selection_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'plugin_id';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['plugin_id'] = DataDefinition::create('string')
      ->setLabel(t('Plugin ID'))
      ->setRequired(TRUE);

    $properties['settings'] = MapDataDefinition::create()
      ->setLabel(t('Settings'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'plugin_id' => [
          'description' => 'The block plugin id',
          'type' => 'varchar',
          'length' => 255,
        ],
        'settings' => [
          'description' => 'Serialized array of settings for the block.',
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
      'indexes' => ['plugin_id' => ['plugin_id']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();

    /** @var \Drupal\block_field\BlockFieldSelectionManager $block_field_selection_manager */
    $block_field_selection_manager = \Drupal::service('plugin.manager.block_field_selection');
    $options = $block_field_selection_manager->getOptions();
    $form = [
      '#type' => 'container',
      '#process' => [[get_class($this), 'fieldSettingsAjaxProcess']],
      '#element_validate' => [[get_class($this), 'fieldSettingsFormValidate']],

    ];
    $form['selection'] = [
      '#type' => 'details',
      '#title' => $this->t('Available blocks'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#process' => [[get_class($this), 'formProcessMergeParent']],
    ];

    $form['selection']['selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Selection method'),
      '#options' => $options,
      '#default_value' => $field->getSetting('selection'),
      '#required' => TRUE,
      '#ajax' => TRUE,
      '#limit_validation_errors' => [],
    ];
    $form['selection']['selection_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Change selection'),
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => [[get_class($this), 'settingsAjaxSubmit']],
    ];

    $form['selection']['selection_settings'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['block_field-settings']],
    ];

    $selection = $block_field_selection_manager->getSelectionHandler($field);
    $form['selection']['selection_settings'] += $selection->buildConfigurationForm([], $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('plugin_id')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'settings' => [],
      ];
    }
    // Unserialize the values.
    if (is_string($values['settings'])) {
      $values['settings'] = unserialize($values['settings']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlock() {
    if (empty($this->plugin_id)) {
      return NULL;
    }

    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');

    /** @var \Drupal\Core\Block\BlockPluginInterface $block_instance */
    $block_instance = $block_manager->createInstance($this->plugin_id, $this->settings);

    $plugin_definition = $block_instance->getPluginDefinition();

    // Don't return broken block plugin instances.
    if ($plugin_definition['id'] == 'broken') {
      return NULL;
    }

    // Don't return broken block content instances.
    if ($plugin_definition['id'] == 'block_content') {
      $uuid = $block_instance->getDerivativeId();
      if (!\Drupal::service('entity.repository')->loadEntityByUuid('block_content', $uuid)) {
        return NULL;
      }
    }

    return $block_instance;
  }

  /**
   * Render API callback.
   *
   * Processes the field settings form and allows access to
   * the form state.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
   * @see static::fieldSettingsForm()
   */
  public static function fieldSettingsAjaxProcess($form, FormStateInterface $form_state) {
    static::fieldSettingsAjaxProcessElement($form, $form);
    return $form;
  }

  /**
   * Adds block_field specific properties to AJAX form elements from settings.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
   * @see static::fieldSettingsAjaxProcess()
   */
  public static function fieldSettingsAjaxProcessElement(&$element, $main_form) {
    if (!empty($element['#ajax'])) {
      $element['#ajax'] = [
        'callback' => [get_called_class(), 'settingsAjax'],
        'wrapper' => $main_form['#id'],
        'element' => $main_form['#array_parents'],
      ];
    }

    foreach (Element::children($element) as $key) {
      static::fieldSettingsAjaxProcessElement($element[$key], $main_form);
    }
  }

  /**
   * Ajax callback for the selection settings form.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
   * @see static::fieldSettingsForm()
   */
  public static function settingsAjax($form, FormStateInterface $form_state) {
    return NestedArray::getValue($form, $form_state->getTriggeringElement()['#ajax']['element']);
  }

  /**
   * Submit selection for the non-JS case.
   *
   * @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem
   * @see static::fieldSettingsForm()
   */
  public static function settingsAjaxSubmit($form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * Render API callback.
   *
   * Moves block_field specific Form API elements
   * (i.e. 'selection_settings') up a level for easier processing by the
   * validation and submission selections.
   */
  public static function formProcessMergeParent($element) {
    $parents = $element['#parents'];
    array_pop($parents);
    $element['#parents'] = $parents;
    return $element;
  }

  /**
   * Form element validation handler; Invokes selection plugin's validation.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function fieldSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $field = $form_state->getFormObject()->getEntity();
    $handler = \Drupal::service('plugin.manager.block_field_selection')->getSelectionHandler($field);
    $handler->validateConfigurationForm($form, $form_state);
  }

}
