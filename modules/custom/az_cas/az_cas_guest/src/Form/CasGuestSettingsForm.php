<?php

namespace Drupal\az_cas_guest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Quickstart CAS Guest Authentication settings.
 */
class CasGuestSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_cas_guest_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_cas_guest.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('az_cas_guest.settings');

    $form['authentication_mode'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authentication Mode'),
      '#description' => $this->t('Configure how Quickstart CAS users are authenticated in Drupal.'),
    ];

    $form['authentication_mode']['prevent_user_creation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable guest authentication mode'),
      '#description' => $this->t('When enabled, Quickstart CAS authentication will not create individual Drupal user accounts for new users. Existing Drupal users will still be able to log in normally.'),
      '#default_value' => $config->get('prevent_user_creation'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('az_cas_guest.settings')
      ->set('prevent_user_creation', $form_state->getValue('prevent_user_creation'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}