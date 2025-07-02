<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'numeric' class.
 */
abstract class NumericBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Form validation.
      'readonly' => FALSE,
      'size' => '',
      'placeholder' => '',
      'autocomplete' => 'on',
    ] + parent::defineDefaultProperties();
  }

  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    if ($this->hasProperty('step') && !isset($element['#step'])) {
      $element['#step'] = $this->getDefaultProperty('step') ?: 'any';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $min = $element['#min'] ?? 1;
    $max = $element['#max'] ?? 10;

    // Replace tokens.
    if (is_string($min)) {
      $min = $this->tokenManager->replace($min, $webform);
    }
    if (is_string($max)) {
      $max = $this->tokenManager->replace($max, $webform);
    }

    // Make sure a min/max is set.
    if (!is_numeric($min) || !is_numeric($max)) {
      $min = 1;
      $max = 10;
    }

    return [$min, floor((($max - $min) / 2) + $min), $max];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $element_properties = $form_state->get('element_properties');
    $min = $element_properties['min'] ?? 0;
    $max = $element_properties['max'] ?? 0;
    $step = $element_properties['step'] ?? 0;
    // If all properties are numeric use number input, else allow for tokens.
    if (is_numeric($min) && is_numeric($max) && is_numeric($step)) {
      $form['number'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Number settings'),
      ];
      $form['number']['number_container'] = $this->getFormInlineContainer();
      $form['number']['number_container']['min'] = [
        '#type' => 'number',
        '#title' => $this->t('Minimum'),
        '#description' => $this->t('Specifies the minimum value.'),
        '#step' => 'any',
        '#size' => 4,
      ];
      $form['number']['number_container']['max'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum'),
        '#description' => $this->t('Specifies the maximum value.'),
        '#step' => 'any',
        '#size' => 4,
      ];
      $form['number']['number_container']['step'] = [
        '#type' => 'number',
        '#title' => $this->t('Steps'),
        '#description' => $this->t('Specifies the legal number intervals. Leave blank to support any number interval. Decimals are supported.'),
        '#step' => 'any',
        '#size' => 4,
      ];
      return $form;
    }
    else {
      $form['number'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Number settings'),
      ];
      $form['number']['min'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Minimum'),
        '#description' => $this->t('Specifies the minimum value.'),
      ];
      $form['number']['max'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Maximum'),
        '#description' => $this->t('Specifies the maximum value.'),
      ];
      $form['number']['step'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Steps'),
        '#description' => $this->t('Specifies the legal number intervals. Leave blank to support any number interval. Decimals are supported.'),
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Validate min/max value.
    $min = $form_state->getValue('min');
    $max = $form_state->getValue('max');
    if (($min === '' || !isset($min) || !is_numeric($min)) || ($max === '' ||  !isset($max)) || !is_numeric($max)) {
      return;
    }

    if ($min >= $max) {
      $form_state->setErrorByName('min', $this->t('Minimum value can not exceed the maximum value.'));
    }
  }

}
