<?php

namespace Drupal\environment_indicator_ui\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form for Environment Indicator.
 */
class EnvironmentIndicatorUISettingsForm extends ConfigFormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'environment_indicator_ui_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.indicator');
    $form = parent::buildForm($form, $form_state);
    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Environment Name'),
      '#description' => $this->t('Enter a name for this environment to be displayed in the admin toolbar.'),
      '#default_value' => $config->get('name'),
    ];
    $form['fg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Foreground Color'),
      '#description' => $this->t('Foreground color for the admin toolbar. Ex: #0D0D0D.'),
      '#default_value' => $config->get('fg_color'),
    ];
    $form['bg_color'] = [
      '#type' => 'color',
      '#title' => $this->t('Background Color'),
      '#description' => $this->t('Background color for the admin toolbar. Example: #4298f4.'),
      '#default_value' => $config->get('bg_color'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['environment_indicator.indicator'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $colors = [
      'fg_color' => $form_state->getValue('fg_color'),
      'bg_color' => $form_state->getValue('bg_color'),
    ];

    foreach ($colors as $property_name => $color) {
      if (!preg_match('/^#[a-f0-9]{6}$/i', $color)) {
        $form_state->setErrorByName($property_name, $this->t('Please enter a valid hex value.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('environment_indicator.indicator');
    $properties = ['name', 'fg_color', 'bg_color'];
    array_walk($properties, function ($property) use ($config, $form_state) {
      $config->set($property, $form_state->getValue($property));
    });
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
