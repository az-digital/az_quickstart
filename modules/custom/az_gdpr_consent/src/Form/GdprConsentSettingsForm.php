<?php

namespace Drupal\az_gdpr_consent\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Quickstart GDPR Consent Management settings.
 */
class GdprConsentSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_gdpr_consent_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_gdpr_consent.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('az_gdpr_consent.settings');

    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable geolocation-based consent management'),
      '#description' => $this->t('When enabled, Klaro consent banner will only be shown to visitors from countries that require GDPR compliance.'),
      '#default_value' => $config->get('enabled'),
    ];

    $form['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#description' => $this->t('When enabled, consent banner will always be shown regardless of visitor location. Useful for testing.'),
      '#default_value' => $config->get('test_mode'),
    ];

    $form['show_on_unknown_location'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show consent banner when location is unknown'),
      '#description' => $this->t('When Smart IP cannot determine the visitor location, show the consent banner. This is the safer option for compliance.'),
      '#default_value' => $config->get('show_on_unknown_location'),
    ];

    $form['target_countries'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Target country codes'),
      '#description' => $this->t('Enter two-letter ISO country codes (one per line) for countries that should see the consent banner. Default list includes EU/EEA countries, UK, and countries with similar data protection laws.'),
      '#default_value' => implode("\n", $config->get('target_countries') ?? []),
      '#rows' => 15,
    ];

    $form['country_info'] = [
      '#type' => 'details',
      '#title' => $this->t('Country code reference'),
      '#open' => FALSE,
    ];

    $form['country_info']['reference'] = [
      '#markup' => $this->t('<p><strong>Default list includes:</strong></p>
        <ul>
          <li>27 EU Member States</li>
          <li>3 EEA Countries (Iceland, Liechtenstein, Norway)</li>
          <li>United Kingdom (UK GDPR)</li>
          <li>11 European countries not implementing GDPR but subject to compliance</li>
          <li>15 countries with similar data protection laws (Switzerland, Canada, Brazil, Japan, etc.)</li>
        </ul>
        <p>Use two-letter ISO 3166-1 alpha-2 country codes. For example: US, CA, GB, DE, FR</p>'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Parse country codes from textarea.
    $country_codes = $form_state->getValue('target_countries');
    $country_array = array_filter(array_map('trim', explode("\n", $country_codes)));

    $this->config('az_gdpr_consent.settings')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('test_mode', $form_state->getValue('test_mode'))
      ->set('show_on_unknown_location', $form_state->getValue('show_on_unknown_location'))
      ->set('target_countries', $country_array)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
