<?php

namespace Drupal\az_cas_guest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Configure CAS Guest Authentication settings.
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
      '#description' => $this->t('Configure how CAS users are authenticated in Drupal.'),
    ];

    $form['authentication_mode']['prevent_user_creation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable guest authentication mode'),
      '#description' => $this->t('When enabled, CAS authentication will not create individual Drupal user accounts for new users. Existing Drupal users will still be able to log in normally.'),
      '#default_value' => $config->get('prevent_user_creation'),
    ];

    $form['authentication_mode']['use_shared_account'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use shared guest account'),
      '#description' => $this->t('When enabled, new CAS users will be logged in as a shared guest account instead of remaining anonymous.'),
      '#default_value' => $config->get('use_shared_account'),
      '#states' => [
        'visible' => [
          ':input[name="prevent_user_creation"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['shared_account'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Shared Guest Account Settings'),
      '#description' => $this->t('Configure the shared guest account used for CAS authentication.'),
      '#states' => [
        'visible' => [
          ':input[name="use_shared_account"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['shared_account']['guest_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Guest username'),
      '#description' => $this->t('The username for the shared guest account.'),
      '#default_value' => $config->get('guest_username') ?: 'cas_guest',
      '#required' => FALSE,
    ];

    $form['shared_account']['guest_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Guest email'),
      '#description' => $this->t('The email address for the shared guest account.'),
      '#default_value' => $config->get('guest_email') ?: 'cas_guest@example.com',
      '#required' => FALSE,
    ];

    // Get all roles except anonymous and authenticated.
    $roles = user_roles(TRUE);
    unset($roles[RoleInterface::AUTHENTICATED_ID]);
    $role_options = [];
    foreach ($roles as $role_id => $role) {
      $role_options[$role_id] = $role->label();
    }

    $form['shared_account']['guest_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Guest roles'),
      '#description' => $this->t('The roles to assign to the shared guest account.'),
      '#options' => $role_options,
      '#default_value' => $config->get('guest_roles') ?: [],
    ];

    $form['redirect'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirect Settings'),
    ];

    $form['redirect']['redirect_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect path after authentication'),
      '#description' => $this->t('The path to redirect to after successful guest authentication. Leave empty to use the homepage.'),
      '#default_value' => $config->get('redirect_path'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Filter out unselected roles.
    $guest_roles = array_filter($form_state->getValue('guest_roles'));

    $this->config('az_cas_guest.settings')
      ->set('prevent_user_creation', $form_state->getValue('prevent_user_creation'))
      ->set('use_shared_account', $form_state->getValue('use_shared_account'))
      ->set('guest_username', $form_state->getValue('guest_username'))
      ->set('guest_email', $form_state->getValue('guest_email'))
      ->set('guest_roles', $guest_roles)
      ->set('redirect_path', $form_state->getValue('redirect_path'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}