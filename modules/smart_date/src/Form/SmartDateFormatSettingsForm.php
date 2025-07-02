<?php

namespace Drupal\smart_date\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SmartDateSettingsForm.
 *
 * @ingroup smart_date
 */
class SmartDateFormatSettingsForm extends FormBase {

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'smart_date_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('smart_date_settings.settings');
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, 'smart_date_format_') !== FALSE) {
        $config->set(str_replace('smart_date_format_', '', $key), $value);
      }
    }
    $config->save();
    $this->messenger()->addMessage($this->t('Configuration was saved.'));
  }

  /**
   * Defines the settings form for Smart Date Format entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['searchoverride_settings']['#markup'] = 'Settings form for Smart date formats. Manage configuration here.';
    $config = $this->config('searchoverride_settings.settings');
    $form['smart_date_format_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Path'),
      '#default_value' => $config->get('path'),
      '#placeholder' => '/search',
      '#description' => $this->t('The site-relative path to the search results page. Be sure to include the preceding slash'),
      '#required' => TRUE,
    ];
    $form['smart_date_format_parameter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL Parameter'),
      '#placeholder' => 'query',
      '#default_value' => $config->get('parameter'),
      '#description' => $this->t('The URL parameter through which search keywords are passed'),
      '#required' => TRUE,
    ];
    $form['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

}
