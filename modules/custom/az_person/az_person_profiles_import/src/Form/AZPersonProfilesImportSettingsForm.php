<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Quickstart Person Profiles Import settings for this site.
 */
final class AZPersonProfilesImportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_person_profiles_import_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['az_person_profiles_import.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('az_person_profiles_import.settings');
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Profiles API Endpoint'),
      '#description' => $this->t('Enter a fully qualified URL for the endpoint of the profiles API service.'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
    ];
    $form['apikey'] = [
      '#type' => 'password',
      '#title' => $this->t('API Token'),
      '#description' => $this->t('Enter an API Token for the profiles API service.'),
      '#maxlength' => 128,
      '#size' => 64,
      '#default_value' => $config->get('apikey'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('az_person_profiles_import.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('apikey', $form_state->getValue('apikey'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
