<?php

/**
 * @file
 * Integrates third party settings for the Webform module.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Implements hook_webform_admin_third_party_settings_form_alter().
 */
function webform_test_third_party_settings_webform_admin_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  $form['third_party_settings']['webform_test_third_party_settings'] = [
    '#type' => 'details',
    '#title' => t('Webform test third party admin settings'),
    '#open' => TRUE,
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['message'] = [
    '#type' => 'textfield',
    '#title' => t('Display the below message on every webform'),
    '#default_value' => $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'message'),
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['error'] = [
    '#type' => 'checkbox',
    '#title' => t('Display as error'),
    '#default_value' => $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'error'),
    '#return_value' => TRUE,
    '#states' => [
      'visible' => [
        ':input[name="third_party_settings[webform_test_third_party_settings][message]"]' => ['filled' => TRUE],
      ],
    ],
  ];

  $form['#validate'][] = '_webform_test_third_party_settings_form_validate';
}

/**
 * Implements hook_webform_third_party_settings_form_alter().
 */
function webform_test_third_party_settings_webform_third_party_settings_form_alter(&$form, FormStateInterface $form_state) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $form_state->getFormObject()->getEntity();

  $form['third_party_settings']['webform_test_third_party_settings'] = [
    '#type' => 'details',
    '#title' => t('Webform test third party settings'),
    '#open' => TRUE,
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['message'] = [
    '#type' => 'textfield',
    '#title' => t('Display the below message'),
    '#default_value' => $webform->getThirdPartySetting('webform_test_third_party_settings', 'message'),
  ];
  $form['third_party_settings']['webform_test_third_party_settings']['error'] = [
    '#type' => 'checkbox',
    '#title' => t('Display as error'),
    '#default_value' => $webform->getThirdPartySetting('webform_test_third_party_settings', 'error'),
    '#return_value' => TRUE,
    '#states' => [
      'visible' => [
        ':input[name="third_party_settings[webform_test_third_party_settings][message]"]' => ['filled' => TRUE],
      ],
    ],
  ];

  $form['#validate'][] = '_webform_test_third_party_settings_form_validate';
}

/**
 * Validate callback; Cleans up third party settings.
 */
function _webform_test_third_party_settings_form_validate(&$form, FormStateInterface $form_state) {
  $third_party_settings = $form_state->getValue('third_party_settings');
  if (empty($third_party_settings['webform_test_third_party_settings']['message'])) {
    $third_party_settings['webform_test_third_party_settings'] = NULL;
  }
  $form_state->setValue('third_party_settings', $third_party_settings);
}

/**
 * Implements hook_webform_submission_form_alter().
 */
function webform_test_third_party_settings_webform_submission_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // phpcs:ignore DrupalPractice.Variables.GetRequestData.SuperglobalAccessed
  if (!empty($_POST)) {
    return;
  }

  /** @var \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager */
  $third_party_settings_manager = \Drupal::service('webform.third_party_settings_manager');

  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $form_state->getFormObject()->getEntity();
  $webform = $webform_submission->getWebform();

  // Get message from the webform settings or the webform admin settings.
  $message = $webform->getThirdPartySetting('webform_test_third_party_settings', 'message') ?:
    $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'message');

  $error = $webform->getThirdPartySetting('webform_test_third_party_settings', 'error') ?:
    $third_party_settings_manager->getThirdPartySetting('webform_test_third_party_settings', 'error');

  // If a message is set, display it.
  if ($message) {
    \Drupal::messenger()->addMessage($message, $error ? MessengerInterface::TYPE_ERROR : MessengerInterface::TYPE_STATUS);
  }
}
