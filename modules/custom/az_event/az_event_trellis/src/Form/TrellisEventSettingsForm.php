<?php

namespace Drupal\az_event_trellis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for custom Trellis Events Importer module settings.
 */
class TrellisEventSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_event_trellis_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_event_trellis.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['api_hostname'] = [
      '#title' => t("API Hostname"),
      '#type' => 'textfield',
      '#config_target' => 'az_event_trellis.settings:api_hostname',
    ];

    return parent::buildForm($form, $form_state);
  }

}
