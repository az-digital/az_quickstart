<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\Plugin\Field\FieldType\DateRangeItem;

/**
 * Custom DateRangeItem for optional end_value.
 */
class OptionalEndDateDateRangeItem extends DateRangeItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'optional_end_date' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['end_value']->setRequired(FALSE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    if ($this->getFieldDefinition()->getFieldStorageDefinition()->getSetting('optional_end_date')) {
      $start_value = $this->get('value')->getValue();
      return $start_value === NULL || $start_value === '';
    }

    return parent::isEmpty();
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $element = parent::storageSettingsForm($form, $form_state, $has_data);

    $element['optional_end_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Optional end date'),
      '#default_value' => $this->getSetting('optional_end_date'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraint_manager = \Drupal::typedDataManager()
      ->getValidationConstraintManager();
    $constraints = parent::getConstraints();

    if (empty($this->getSetting('optional_end_date'))) {
      $label = $this->getFieldDefinition()->getLabel();
      $constraints[] = $constraint_manager
        ->create('ComplexData', [
          'end_value' => [
            'NotNull' => [
              'message' => $this->t('The @title end date is required', ['@title' => $label]),
            ],
          ],
        ]);
    }

    return $constraints;
  }

}
