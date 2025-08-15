<?php

namespace Drupal\az_eds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for AZ EDS settings.
 */
class AZEDSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_eds_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_eds.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_eds_config = $this->config('az_eds.settings');

    $form['students_allowed'] = [
      '#title' => t('Allow EDS queries to retrieve students'),
      '#type' => 'checkbox',
      '#description' => t("Check to allow EDS migrations to return student information."),
      '#default_value' => $az_eds_config->get('students_allowed'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_eds.settings')
      ->set('students_allowed', $form_state->getValue('students_allowed'))
      ->save();
    parent::submitForm($form, $form_state);

  }

}
