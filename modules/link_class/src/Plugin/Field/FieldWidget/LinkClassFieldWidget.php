<?php

namespace Drupal\link_class\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Plugin implementation of the 'link_class_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "link_class_field_widget",
 *   label = @Translation("Link with class"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkClassFieldWidget extends LinkWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'link_class_mode' => 'manual',
      'link_class_force' => '',
      'link_class_select' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);
    $field_name = $this->fieldDefinition->getName();

    $element['link_class_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method for adding class'),
      '#options' => $this->getModeOptions(),
      '#default_value' => $this->getSetting('link_class_mode'),
      '#description' => $this->t('Select the method you want to use for adding class.'),
    ];

    $element['link_class_force'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link classes'),
      '#default_value' => $this->getSetting('link_class_force'),
      '#description' => $this->t('Set the classes to add on each link. Classes must be separated by a space.'),
      '#attributes' => [
        'placeholder' => 'btn btn-default',
      ],
      '#size' => '30',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][link_class_mode]"]' => ['value' => 'force_class'],
        ],
      ],
    ];

    $element['link_class_select'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Define possibles classes'),
      '#default_value' => $this->getSetting('link_class_select'),
      '#description' => $this->selectClassDescription(),
      '#attributes' => [
        'placeholder' => 'btn btn-default|Default button' . PHP_EOL . 'btn btn-primary|Primary button',
      ],
      '#size' => '30',
      '#states' => [
        'visible' => [
          ':input[name="fields[' . $field_name . '][settings_edit_form][settings][link_class_mode]"]' => ['value' => 'select_class'],
        ],
      ],
    ];

    return $element;
  }

  /**
   * Return the description for the class select mode.
   */
  protected function selectClassDescription() {
    $description = '<p>' . t('The possible classes this link can have. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . t('The key is the string which will be used as a class on a link. The label will be used on edit forms.');
    $description .= '<br/>' . t('If the key contains several classes, each class must be separated by a <strong>space</strong>.');
    $description .= '<br/>' . t('The label is optional: if a line contains a single string, it will be used as key and label.');
    $description .= '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $option = $this->getSetting('link_class_mode');
    $summary[] = $this->t('Mode: @link_class_mode', ['@link_class_mode' => $this->getModeOptions($option)]);
    if ($option == 'force_class') {
      $summary[] = $this->t('Class(es) added: @link_class_force', ['@link_class_force' => $this->getSetting('link_class_force')]);
    }
    if ($option == 'select_class') {
      $classes_available = $this->getSelectOptions($this->getSetting('link_class_select'), TRUE);
      $summary[] = $this->t('Class(es) available: @link_class_select', ['@link_class_select' => $classes_available]);
    }

    return $summary;
  }

  /**
   * Return the options availables for the widget.
   *
   * @param string|null $key
   *   The optionnal key to retrieve.
   *
   * @return array|mixed
   *   The options array or the value corresponding to $key.
   */
  protected function getModeOptions($key = NULL) {
    $options = [
      'force_class' => $this->t('Class are automatically added'),
      'select_class' => $this->t('Let users select a class from a list'),
      'manual' => $this->t('Users can set a class manually'),
    ];

    if ($key && isset($options[$key])) {
      return $options[$key];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    /** @var \Drupal\link\LinkItemInterface $item */
    $item = $items[$delta];
    $options = $item->get('options')->getValue();

    $mode = $this->getSetting('link_class_mode');
    switch ($mode) {
      case 'manual':
        $element['options']['attributes']['class'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Link classes'),
          '#default_value' => !empty($options['attributes']['class']) ? $options['attributes']['class'] : '',
          '#description' => $this->t('Add classes to the link. The classes must be separated by a space.'),
          '#size' => '30',
        ];
        break;

      case 'select_class':
        /** @var \Drupal\link\LinkItemInterface $item */
        $classes_available = $this->getSelectOptions($this->getSetting('link_class_select'));
        $default_value = !empty($options['attributes']['class']) ? $options['attributes']['class'] : '';
        $element['options']['attributes']['class'] = [
          '#type' => 'select',
          '#title' => $this->t('Select a style'),
          '#options' => ['' => $this->t('- None -')] + $classes_available,
          '#default_value' => $default_value,
        ];
        break;

      case 'force_class':
        $element['options']['attributes']['class'] = [
          '#type' => 'value',
          '#value' => $this->getSetting('link_class_force'),
        ];
        break;

    }

    return $element;
  }

  /**
   * Convert textarea lines into an array.
   *
   * @param string $string
   *   The textarea lines to explode.
   * @param bool $summary
   *   A flag to return a formatted list of classes available.
   *
   * @return array
   *   An array keyed by the classes.
   */
  protected function getSelectOptions($string, $summary = FALSE) {
    $options = [];
    $lines = preg_split("/\\r\\n|\\r|\\n/", trim($string));
    $lines = array_filter($lines);

    foreach ($lines as $line) {
      list($class, $label) = explode('|', trim($line));
      $label = $label ?: $class;
      $options[$class] = $label;
    }

    if ($summary) {
      return implode(', ', array_keys($options));
    }

    return $options;

  }

}
