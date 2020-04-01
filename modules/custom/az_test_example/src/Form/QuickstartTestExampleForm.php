<?php

namespace Drupal\az_test_example\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class QuickstartTestExampleForm.
 */
class QuickstartTestExampleForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'az_test_example.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'quickstart_test_example_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('az_test_example.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Enter the API Key setting.'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Check that a key is valid.
   *
   * @param string $key
   *   The string key to check for correctness.
   *
   * @return bool
   *   True if the key was valid, otherwise false.
   */
  public static function isKeyValid(string $key) {
    // Realistically this might contact a webservice to verify the key.
    // For our purposes, consider a valid input to be any string starting
    // with V1 followed by 10 or more alphanumeric characters.
    return preg_match('/V1\w{10,}/', $key) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (!empty($values['api_key']) && !$this->isKeyValid($values['api_key'])) {
      $form_state->setError($form, "Enter a valid API key.");
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('az_test_example.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();
  }

}
