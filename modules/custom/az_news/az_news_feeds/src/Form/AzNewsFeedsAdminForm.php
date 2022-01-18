<?php

namespace Drupal\az_news_feeds\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing news importer source.
 */
class AzNewsFeedsAdminForm extends ConfigFormBase {

  /**
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

   /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a AzNewsFeedsAdminForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param GuzzleHttp\ClientInterface $http_client
   *   An http client.
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migration_plugin_manager
   *   Plugin manager for migration plugins.

   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    MigrationPluginManager $migration_plugin_manager
    ) {
    parent::__construct($config_factory);
    $this->httpClient = $http_client;
    $this->migrationPluginManager = $migration_plugin_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('plugin.manager.migration')

    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'migrate_plus.migration_group.az_news_feeds',
      'az_news_feeds.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'az_news_feeds_admin';
  }

  /**
   * Returns array of term options from UArizona News.
   */
  public function getRemoteTermOptions(): array {
    $az_news_feeds_config = $this->config('az_news_feeds.settings');
    $base_uri = $az_news_feeds_config->get('uarizona_news_base_uri');
    $category_path = $az_news_feeds_config->get('uarizona_news_category_path');
    $selected_vocabularies = $az_news_feeds_config->get('uarizona_news_vocabularies');
    $views_contextual_argument = implode('+', array_keys($selected_vocabularies));
    $news_category_url = $base_uri . $category_path . $views_contextual_argument;
    // Get category options remotely.
    $response = $this->httpClient->request('GET', $news_category_url, ['verify' => FALSE]);
    $terms = json_decode($response->getBody(), TRUE);

    $options = ['all' => 'All'];
    foreach ($terms['terms'] as $key => $value) {
      $options[$value['term']['tid']] = $value['term']['name'];
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $az_news_feeds_config = $this->config('az_news_feeds.settings');
    $config = $this->config('migrate_plus.migration_group.az_news_feeds');
    $selected_categories = $az_news_feeds_config->get('uarizona_news_terms');
    $selected_categories = array_keys($selected_categories);
    $term_options = $this->getRemoteTermOptions();

    $form['term_options'] = [
      '#type' => 'value',
      '#value' => $term_options,
    ];

    $form['uarizona_news_terms'] = [
      '#title' => t('News Categories'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => 'Select which terms you want to use.',
      '#options' => $form['term_options']['#value'],
      '#default_value' => $selected_categories,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $az_news_feeds_config = $this->configFactory->getEditable('az_news_feeds.settings');
    $keys = $form_state->getValue('uarizona_news_terms');
    $selected_terms = [];
    foreach ($keys as $key) {
      $selected_terms[$key] = $form['uarizona_news_terms']['#options'][$key];
    }
    $az_news_feeds_config
      ->set('uarizona_news_terms', $selected_terms)
      ->save();

    drupal_flush_all_caches();

    $tag = 'Quickstart News Feeds';

    // Rollback the migrations for the old endpoint.
    $migrations = $this->migrationPluginManager->createInstancesByTag($tag);
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->rollback();
    }

    // Run the migrations for the new endpoint.
    $migrations = $this->migrationPluginManager->createInstancesByTag($tag);
    foreach ($migrations as $migration) {
      $executable = new MigrateExecutable($migration, new MigrateMessage());
      $executable->import();
    }

    parent::submitForm($form, $form_state);

  }

}
