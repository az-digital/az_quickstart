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
    $az_event_trellis_config = $this->config('az_event_trellis.settings');

    $form['api_hostname'] = [
      '#title' => $this->t("API Hostname"),
      '#type' => 'textfield',
      '#default_value' => $az_event_trellis_config->get('api_hostname'),
    ];

    $form['update_limit'] = [
      '#title' => $this->t("Event Update Limit"),
      '#description' => $this->t('Events older than this window will not be updated automatically, <a href="@datedocs">Use a php relative date</a>, e.g. <em>now - 180 days</em>.', [
        '@datedocs' => 'https://www.php.net/manual/en/datetime.formats.php',
      ]),
      '#type' => 'textfield',
      '#default_value' => $az_event_trellis_config->get('update_limit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $limit = $form_state->getValue('update_limit');
    try {
      if (!empty($limit)) {
        // Attempt to process the expiration range to check for an exception.
        // phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
        $date = new \DateTimeImmutable($limit, new \DateTimeZone('UTC'));
      }
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('update_limit', $this->t('You must use a valid date modifier, e.g. now - 180 days'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_event_trellis.settings')
      ->set('api_hostname', $form_state->getValue('api_hostname'))
      ->set('update_limit', $form_state->getValue('update_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
