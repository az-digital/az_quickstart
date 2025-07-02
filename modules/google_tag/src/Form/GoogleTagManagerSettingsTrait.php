<?php

namespace Drupal\google_tag\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines shared routines for the container and settings forms.
 */
trait GoogleTagManagerSettingsTrait {

  /**
   * Fieldset builder for the container settings form.
   */
  public function gtmAdvancedFieldset(array $advanced_settings, string $gtmId) {
    // Build form elements.
    $fieldset = [
      '#type' => 'details',
      '#title' => $this->t('Google Tag Manager: %gtmid', ['%gtmid' => $gtmId]),
      '#group' => 'advanced_settings',
      '#tree' => TRUE,
    ];

    $fieldset['data_layer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data layer'),
      '#description' => $this->t('The name of the data layer. Default value is "dataLayer". In most cases, use the default.'),
      '#default_value' => $advanced_settings['data_layer'] ?? 'dataLayer',
      '#placeholder' => 'dataLayer',
      '#required' => TRUE,
    ];

    $fieldset['include_classes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add classes to the data layer'),
      '#description' => $this->t('If checked, then the listed classes will be added to the data layer.'),
      '#default_value' => $advanced_settings['include_classes'] ?? FALSE,
    ];

    $description = $this->t('The types of tags, triggers, and variables <strong>allowed</strong> on a page. Enter one class per line. For more information, refer to the <a href="https://developers.google.com/tag-manager/devguide#security">developer documentation</a>.');

    $fieldset['allowlist_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed classes'),
      '#description' => $description,
      '#default_value' => $advanced_settings['allowlist_classes'] ?? '',
      '#rows' => 5,
      '#states' => $this->statesArray('advanced_settings[gtm][' . $gtmId . '][include_classes]'),
    ];

    $fieldset['blocklist_classes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blocked classes'),
      '#description' => $this->t('The types of tags, triggers, and variables <strong>forbidden</strong> on a page. Enter one class per line.'),
      '#default_value' => $advanced_settings['blocklist_classes'] ?? '',
      '#rows' => 5,
      '#states' => $this->statesArray('advanced_settings[gtm][' . $gtmId . '][include_classes]'),
    ];

    $fieldset['include_environment'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include an environment'),
      '#description' => $this->t('If checked, then the applicable snippets will include the environment items below. Enable <strong>only for development</strong> purposes.'),
      '#default_value' => $advanced_settings['include_environment'] ?? FALSE,
    ];

    $description = $this->t('The environment ID to use with this website container. To get an environment ID, <a href="https://tagmanager.google.com/#/admin">select Environments</a>, create an environment, then click the "Get Snippet" action. The environment ID and token will be in the snippet.');

    $fieldset['environment_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment ID'),
      '#description' => $description,
      '#default_value' => $advanced_settings['environment_id'] ?? '',
      '#placeholder' => 'env-x',
      '#size' => 10,
      '#maxlength' => 7,
      '#states' => $this->statesArray('advanced_settings[gtm][' . $gtmId . '][include_environment]'),
    ];

    $fieldset['environment_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment token'),
      '#description' => $this->t('The authentication token for this environment.'),
      '#default_value' => $advanced_settings['environment_token'] ?? '',
      '#placeholder' => 'xxxxxxxxxxxxxxxxxxxxxx',
      '#size' => 20,
      '#maxlength' => 25,
      '#states' => $this->statesArray('advanced_settings[gtm][' . $gtmId . '][include_environment]'),
    ];

    return $fieldset;
  }

  /**
   * Returns states array for a form element.
   *
   * @param string $variable
   *   The name of the form element.
   *
   * @return array
   *   The states array.
   */
  public function statesArray($variable) {
    return [
      'required' => [
        ':input[name="' . $variable . '"]' => ['checked' => TRUE],
      ],
      'invisible' => [
        ':input[name="' . $variable . '"]' => ['checked' => FALSE],
      ],
    ];
  }

  /**
   * Validates GTM form values.
   */
  public function validateGtmFormValues(array &$form, FormStateInterface $form_state) {
    // Trim the text values.
    $advanced_values = $form_state->getValue('advanced_settings');
    if (!is_array($advanced_values) || !isset($advanced_values['gtm']) || !is_array($advanced_values['gtm'])) {
      return;
    }

    foreach ($advanced_values['gtm'] as $gtm_id => $settings) {
      // Skip if $settings is not an array.
      if (!is_array($settings)) {
        continue;
      }

      $environment_id = trim($settings['environment_id']);
      $advanced_values['gtm'][$gtm_id]['data_layer'] = trim($settings['data_layer']);
      $advanced_values['gtm'][$gtm_id]['allowlist_classes'] = $this->cleanText($settings['allowlist_classes']);
      $advanced_values['gtm'][$gtm_id]['blocklist_classes'] = $this->cleanText($settings['blocklist_classes']);
      if (!empty($advanced_values['gtm'][$gtm_id]['include_environment']) && !preg_match('/^env-\d{1,}$/', $environment_id)) {
        $form_state->setError($form['advanced_settings']['gtm'][$gtm_id]['environment_id'], $this->t('A valid environment ID is case sensitive and formatted like env-x.'));
      }
    }

    $form_state->setValue('advanced_settings', $advanced_values);
  }

  /**
   * Cleans a string representing a list of items.
   *
   * @param string $text
   *   The string to clean.
   * @param string $format
   *   The final format of $text, either 'string' or 'array'.
   *
   * @return string
   *   The clean text.
   */
  public function cleanText($text, $format = 'string') {
    $text = explode("\n", $text);
    $text = array_map('trim', $text);
    $text = array_filter($text, 'trim');
    if ($format === 'string') {
      $text = implode("\n", $text);
    }
    return $text;
  }

}
