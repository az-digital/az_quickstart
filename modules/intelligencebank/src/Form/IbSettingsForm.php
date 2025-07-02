<?php

namespace Drupal\ib_dam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IbSettingsForm.
 *
 * Settings form class for module global configuration.
 *
 * @package Drupal\ib_dam\Form
 */
class IbSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ib_dam_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ib_dam.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ib_dam.settings');

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('Check this setting if you want to have more verbose log messages.'),
      '#default_value' => $config->get('debug'),
    ];

    $form['staging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Staging'),
      '#description' => $this->t('Check to enable the beta version of the connector browsing interface.'),
      '#default_value' => $config->get('staging'),
    ];

    $form['allow_embedding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow asset embedding'),
      '#description' => $this->t('Allow embed assets in addition to download.'),
      '#default_value' => $config->get('allow_embedding') ?? FALSE,
    ];

    $form['login'] = [
      '#type'  => 'details',
      '#title' => $this->t('Login Default Values'),
    ];
    $form['login']['enable_custom_url'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable custom Platform URL'),
      '#default_value' => $config->get('login_enable_custom_url') ?? FALSE,
     ];
    $form['login']['enabled_custom_url_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Platform URL (without https://)'),
      '#states' => [
        'visible' => [
          ':input[name="enable_custom_url"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['login']['disabled_custom_url_label'] = [
      '#type' => 'item',
      '#title' => $this->t('Platform Sub-Domain (without https:// or https://www.intelligencebank.com/)'),
      '#states' => [
        'invisible' => [
          ':input[name="enable_custom_url"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['login']['url'] = [
      '#type'          => 'textfield',
      '#default_value' => $config->get('login_url') ?? '',
    ];
    $form['login']['enable_browser_login'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable browser login (for SSO)'),
      '#default_value' => $config->get('login_enable_browser_login') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ib_dam.settings')
      ->set('debug', $form_state->getValue('debug'))
      ->set('staging', $form_state->getValue('staging'))
      ->set('allow_embedding', $form_state->getValue('allow_embedding', TRUE))
      ->set('login_url', $form_state->getValue('url', ''))
      ->set('login_enable_browser_login', (bool) $form_state->getValue('enable_browser_login', FALSE))
      ->set('login_enable_custom_url', (bool) $form_state->getValue('enable_custom_url', FALSE))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
