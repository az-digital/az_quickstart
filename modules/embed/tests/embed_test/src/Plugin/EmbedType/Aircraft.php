<?php

namespace Drupal\embed_test\Plugin\EmbedType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;

/**
 * An embed type plugin for testing the plugin form. Using aircraft.
 *
 * @EmbedType(
 *   id = "embed_test_aircraft",
 *   label = @Translation("Aircraft"),
 * )
 */
class Aircraft extends EmbedTypeBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'aircraft_type' => 'fixed-wing',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['aircraft_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Aircraft type'),
      '#options' => [
        'aerostats' => $this->t('Lighter than air (aerostats)'),
        'fixed-wing' => $this->t('Fixed-wing'),
        'rotorcraft' => $this->t('Rotorcraft'),
        'helicopters' => $this->t('Helicopters'),
        'invalid' => $this->t('Invalid type'),
      ],
      '#default_value' => $this->getConfigurationValue('aircraft_type'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('aircraft_type') === 'invalid') {
      $form_state->setError($form['aircraft_type'], $this->t('Cannot select invalid aircraft type.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('aircraft_type') === 'helicopters') {
      $this->messenger()->addWarning($this->t('Helicopters are just rotorcraft.'));
      $form_state->setValue('aircraft_type', 'rotorcraft');
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return '';
  }

}
