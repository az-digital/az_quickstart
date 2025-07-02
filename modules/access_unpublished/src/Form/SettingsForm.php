<?php

namespace Drupal\access_unpublished\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\options\Plugin\Field\FieldType\ListItemBase;

/**
 * Configure access unpublished settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'access_unpublished.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('access_unpublished.settings');

    $form['hash_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hash key'),
      '#default_value' => $config->get('hash_key'),
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Lifetime'),
      '#description' => $this->t('Default lifetime of the generated access tokens.'),
      '#options' => AccessUnpublishedForm::getDurationOptions(),
      '#default_value' => $config->get('duration'),
    ];

    $form['cleanup_expired_tokens'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cleanup expired tokens'),
      '#description' => $this->t('Cron will cleanup expired tokens.'),
      '#default_value' => $config->get('cleanup_expired_tokens'),
    ];

    $form['cleanup_expired_tokens_period'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Period of time for cron cleanup.'),
      '#default_value' => $config->get('cleanup_expired_tokens_period'),
      '#description' => $this->t("Describe a time by reference to the current day, like '-90 days' (All tokens which expired more than 90 days ago). See <a href=\"http://php.net/manual/function.strtotime.php\">strtotime</a> for more details."),
      '#size' => 60,
      '#maxlength' => 128,
      '#states' => [
        'visible' => [
          ':input[name="cleanup_expired_tokens"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['modify_http_headers'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Modify HTTP Headers on unpublished page.'),
      '#default_value' => $this->prepareHeadersDisplay(),
      '#description' => $this->t("Enter one header per line, in the format key|label."),
      '#element_validate' => [[ListItemBase::class, 'validateAllowedValues']],
      '#field_has_data' => FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('access_unpublished.settings')
      ->set('hash_key', $form_state->getValue('hash_key'))
      ->set('duration', $form_state->getValue('duration'))
      ->set('cleanup_expired_tokens', $form_state->getValue('cleanup_expired_tokens'))
      ->set('cleanup_expired_tokens_period', $form_state->getValue('cleanup_expired_tokens_period'))
      ->set('modify_http_headers', $form_state->getValue('modify_http_headers'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $strtotime = @strtotime($form_state->getValue('cleanup_expired_tokens_period'));
    if (!$strtotime) {
      $form_state->setErrorByName('cleanup_expired_tokens_period', $this->t('The relative start date value entered is invalid.'));
    }
    elseif ($strtotime > time()) {
      $form_state->setErrorByName('cleanup_expired_tokens_period', $this->t('The value has to be negative.'));
    }
  }

  /**
   * Format array to display on settings form.
   *
   * @return string
   *   Return string of HTTP headers.
   */
  private function prepareHeadersDisplay() {
    $headers = $this->config('access_unpublished.settings')
      ->get('modify_http_headers');

    $lines = [];
    foreach ($headers as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

}
