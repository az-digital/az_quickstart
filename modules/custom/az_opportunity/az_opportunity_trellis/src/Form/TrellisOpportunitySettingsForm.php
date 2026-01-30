<?php

namespace Drupal\az_opportunity_trellis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for custom Trellis Opportunity Importer module settings.
 */
class TrellisOpportunitySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_opportunity_trellis_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_opportunity_trellis.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_opportunity_trellis_config = $this->config('az_opportunity_trellis.settings');

    $form['api_hostname'] = [
      '#title' => $this->t("API Hostname"),
      '#type' => 'textfield',
      '#default_value' => $az_opportunity_trellis_config->get('api_hostname'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_opportunity_trellis.settings')
      ->set('api_hostname', $form_state->getValue('api_hostname'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
