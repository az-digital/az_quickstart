<?php

namespace Drupal\az_news_feeds\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing news importer source.
 */
class AzNewsFeedsAdminForm extends ConfigFormBase {

  /**
   * Constructs a AzNewsFeedsAdminForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
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

    $this->config('migrate_plus.migration_group.az_news_feeds')
      ->set('shared_configuration.source.urls', $form_state->getValue('urls'))
      ->save();

      drupal_flush_all_caches();

      $tag = 'Quickstart News Feeds';

      // Rollback the migrations for the old endpoint.
      $migrations = \Drupal::service('plugin.manager.migration')->createInstancesByTag($tag);
      foreach ($migrations as $migration) {
        $executable = new MigrateExecutable($migration, new MigrateMessage());
        $executable->rollback();
      }

      // Run the migrations for the new endpoint.
      $migrations = \Drupal::service('plugin.manager.migration')->createInstancesByTag($tag);
      foreach ($migrations as $migration) {
        $executable = new MigrateExecutable($migration, new MigrateMessage());
        $executable->import();
      }
      
      parent::submitForm($form, $form_state);

  }

}
