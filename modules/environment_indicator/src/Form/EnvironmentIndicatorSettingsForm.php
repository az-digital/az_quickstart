<?php

namespace Drupal\environment_indicator\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Basic Environment Indicator controls form.
 */
class EnvironmentIndicatorSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_indicator_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.settings');

    $form = parent::buildForm($form, $form_state);

    $form['toolbar_integration'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Toolbar integration'),
      '#options' => [
        'toolbar' => $this->t('Toolbar'),
      ],
      '#description' => $this->t('Select the toolbars that you want to integrate with.'),
      '#default_value' => $config->get('toolbar_integration') ?: [],
    ];

    $form['favicon'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show favicon'),
      '#description' => $this->t('If checked, a favicon will be added with the environment colors when the indicator is shown.'),
      '#default_value' => $config->get('favicon') ?: FALSE,
    ];

    $version_identifier_options = [
      'environment_indicator_current_release' => $this->t('Environment Indicator Current Release'),
      'deployment_identifier' => $this->t('Deployment Identifier'),
      'drupal_version' => $this->t('Drupal Version'),
      'none' => $this->t('None'),
    ];

    $form['version_identifier'] = [
      '#type' => 'select',
      '#title' => $this->t('Source of version identifier to display'),
      '#description' => $this->t('Select the source of the version identifier to display in the environment indicator.'),
      '#options' => $version_identifier_options,
      '#default_value' => $config->get('version_identifier') ?: 'deployment_identifier',
      '#ajax' => [
        'callback' => '::updateFallbackOptions',
        'event' => 'change',
        'wrapper' => 'version-identifier-fallback-wrapper',
      ],
    ];

    $version_identifier = $form_state->getValue('version_identifier', $config->get('version_identifier') ?: 'deployment_identifier');

    if ($version_identifier === 'none') {
      $fallback_options = ['none' => $this->t('None')];
    }
    else {
      $fallback_options = array_diff_key($version_identifier_options, [$version_identifier => '']);
    }

    $form['version_identifier_fallback'] = [
      '#type' => 'select',
      '#title' => $this->t('Fallback source of version identifier to display'),
      '#description' => $this->t('Select the fallback source of the version identifier to display in the environment indicator.'),
      '#options' => $fallback_options,
      '#default_value' => $config->get('version_identifier_fallback') ?: 'none',
      '#prefix' => '<div id="version-identifier-fallback-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
          'visible' => [
              ':input[name="version_identifier"]' => ['!value' => 'none']
          ]
      ],
    ];

    return $form;
  }

  /**
   * AJAX callback to update the fallback options.
   */
  public function updateFallbackOptions(array &$form, FormStateInterface $form_state) {
    return $form['version_identifier_fallback'];
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['environment_indicator.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.settings');

    $config->set('toolbar_integration', array_filter($form_state->getValue('toolbar_integration')))
      ->set('favicon', $form_state->getValue('favicon'))
      ->set('version_identifier', $form_state->getValue('version_identifier'))
      ->set('version_identifier_fallback', $form_state->getValue('version_identifier_fallback'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
