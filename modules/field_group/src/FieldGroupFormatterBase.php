<?php

namespace Drupal\field_group;

use Drupal\Core\Field\PluginSettingsBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for 'Fieldgroup formatter' plugin implementations.
 *
 * @ingroup field_group_formatter
 */
abstract class FieldGroupFormatterBase extends PluginSettingsBase implements FieldGroupFormatterInterface {

  /**
   * The group this formatter needs to render.
   *
   * @var object
   */
  protected $group;

  /**
   * The formatter settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The label display setting.
   *
   * @var string
   */
  protected $label;

  /**
   * The view mode.
   *
   * @var string
   */
  protected $viewMode;

  /**
   * The context mode.
   *
   * @var string
   */
  protected $context;

  /**
   * Constructs a FieldGroupFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param object $group
   *   The group object.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label.
   */
  public function __construct($plugin_id, $plugin_definition, \stdClass $group, array $settings, $label) {
    parent::__construct([], $plugin_id, $plugin_definition);

    $this->group = $group;
    $this->settings = $settings;
    $this->label = $label;
    $this->context = $group->context;
  }

  /**
   * Get the current label.
   *
   * @return string
   *   The current label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm() {

    $class = get_class($this);

    $form = [];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field group label'),
      '#default_value' => $this->label,
      '#weight' => -5,
    ];

    $form['show_empty_fields'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display element also when empty'),
      '#description' => $this->t('Display this field group even if the contained fields are currently empty.'),
      '#default_value' => $this->getSetting('show_empty_fields'),
    ];

    $form['label_as_html'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML in label'),
      '#default_value' => $this->getSetting('label_as_html'),
      '#description' => $this->t('Allows using (XSS-filtered) HTML in the label (e.g. icons).'),
      '#weight' => -2,
    ];

    $form['id'] = [
      '#title' => $this->t('ID'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('id'),
      '#weight' => 10,
      '#element_validate' => [[$class, 'validateId']],
    ];

    $form['classes'] = [
      '#title' => $this->t('Extra CSS classes'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('classes'),
      '#weight' => 11,
      '#element_validate' => [[$class, 'validateCssClass']],
    ];

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {

    $summary = [];

    if ($this->getSetting('formatter')) {
      $summary[] = $this->pluginDefinition['label'] . ': ' . $this->getSetting('formatter');
    }

    if ($this->getSetting('show_empty_fields')) {
      $summary[] = $this->t('Show Empty Fields');
    }

    if ($this->getSetting('label_as_html')) {
      $summary[] = $this->t('Allow HTML in label: @label_as_html', [
        '@label_as_html' => $this->getSetting('label_as_html'),
      ]);
    }

    if ($this->getSetting('id')) {
      $summary[] = $this->t('Id: @id', ['@id' => $this->getSetting('id')]);
    }

    if ($this->getSetting('classes')) {
      $summary[] = $this->t('Extra CSS classes: @classes', ['@classes' => $this->getSetting('classes')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return self::defaultContextSettings('view');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultContextSettings($context) {
    return [
      'label_as_html' => FALSE,
      'classes' => '',
      'id' => '',
    ];
  }

  /**
   * Get the classes to add to the group.
   */
  protected function getClasses() {

    $classes = [];
    // Add a required-fields class to trigger the js.
    if ($this->getSetting('required_fields')) {
      $classes[] = 'required-fields';
      $classes[] = 'field-group-' . str_replace('_', '-', $this->getBaseId());
    }

    if ($this->getSetting('classes')) {
      $classes = array_merge($classes, explode(' ', trim($this->getSetting('classes'))));
    }

    return $classes;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$element, $rendering_object) {
    $element['#group_name'] = $this->group->group_name;
    $element['#entity_type'] = $this->group->entity_type;
    $element['#bundle'] = $this->group->bundle;
    $element['#show_empty_fields'] = $this->getSetting('show_empty_fields');
  }

  /**
   * {@inheritdoc}
   */
  public function process(&$element, $processed_object) {

    $element['#group_name'] = $this->group->group_name;
    $element['#entity_type'] = $this->group->entity_type;
    $element['#bundle'] = $this->group->bundle;

    // BC: Call the pre render layer to not break contrib plugins.
    return $this->preRender($element, $processed_object);
  }

  /**
   * Validate the entered css class from the submitted format settings.
   *
   * @param array $element
   *   The validated element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public static function validateCssClass(array $element, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValues();
    $plugin_name = $form_state->get('plugin_settings_edit');
    if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes']) && !preg_match('!^[A-Za-z0-9-_ ]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['classes'])) {
      $form_state->setError($element, t('The css class must include only letters, numbers, underscores and dashes.'));
    }
  }

  /**
   * Validate the entered id attribute from the submitted format settings.
   *
   * @param array $element
   *   The validated element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the form.
   */
  public static function validateId(array $element, FormStateInterface $form_state) {
    $form_state_values = $form_state->getValues();
    $plugin_name = $form_state->get('plugin_settings_edit');
    if (!empty($form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id']) && !preg_match('!^[A-Za-z0-9-_]+$!', $form_state_values['fields'][$plugin_name]['settings_edit_form']['settings']['id'])) {
      $form_state->setError($element, t('The id must include only letters, numbers, underscores and dashes.'));
    }
  }

}
