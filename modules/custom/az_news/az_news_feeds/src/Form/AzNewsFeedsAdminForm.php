<?php

namespace Drupal\az_news_feeds\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class AzNewsFeedsAdminForm extends ConfigFormBase {

  /**
   *
   */
  protected function getEditableConfigNames() {
    return [
      'migrate_plus.migration_group.az_news_feeds',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_news_feeds_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('migrate_plus.migration_group.az_news_feeds');
    $form['urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('UArizona News Feed URLs'),
      '#description' => $this->t('URLs to fetch.'),
      '#default_value' => $config->get('shared_configuration.source.urls'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('migrate_plus.migration_group.az_news_feeds')
      ->set('shared_configuration.source.urls', $form_state->getValue('urls'))
      ->save();
  }

}
