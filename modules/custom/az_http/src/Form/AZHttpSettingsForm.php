<?php

namespace Drupal\az_http\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for AZ Http client settings.
 */
class AZHttpSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_http_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_http.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $az_http_config = $this->config('az_http.settings');

    $form['migrations_http_cached'] = [
      '#title' => $this->t('Cache Migration HTTP Requests'),
      '#type' => 'checkbox',
      '#description' => $this->t("Migrations from remote HTTP endpoints will be cached (recommended)."),
      '#default_value' => $az_http_config->get('migrations.http_cached'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_http.settings')
      ->set('migrations.http_cached', $form_state->getValue('migrations_http_cached'))
      ->save();
    parent::submitForm($form, $form_state);

    // Clear the cache so affected migrations rebuild.
    drupal_flush_all_caches();
  }

}
