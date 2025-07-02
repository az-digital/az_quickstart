<?php

namespace Drupal\chosen_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form;

/**
 * Implements a ChosenConfig form.
 */
class ChosenTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'chosen_config_form';
  }

  /**
   * Chosen test form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['select'] = [
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => $this->t('Select'),
      '#default_value' => '',
      '#empty_option' => $this->t('- Select -'),
      '#options' => ['Option 1', 'Option 2', 'Option 3', 'Option 4'],
      '#chosen' => 1,
    ];

    return $form;
  }

  /**
   * Chosen test form submit handler.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
