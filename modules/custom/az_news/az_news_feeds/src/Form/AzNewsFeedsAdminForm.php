<?php

namespace Drupal\az_news_feeds\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for editing news importer source.
 */
class AzNewsFeedsAdminForm extends ConfigFormBase {

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
   * @param \Drupal\Core\Config\TypedConfigManagerInterface|null $typedConfigManager
   *   The typed config manager.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An http client.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface|null $typedConfigManager,
    ClientInterface $http_client,
  ) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('http_client')
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
   * Returns array of term options from University of Arizona News.
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
    $selected_categories = $az_news_feeds_config->get('uarizona_news_terms');
    $selected_categories = array_keys($selected_categories);
    $term_options = $this->getRemoteTermOptions();
    $form['links'] = [
      '#type' => 'item',
      '#markup' => t('You can @migrate_queue_importer_link, or @migrate_tools_link separately.', [
        '@migrate_queue_importer_link' => Link::fromTextAndUrl(
          'configure the import schedule', Url::fromRoute('entity.cron_migration.collection')
        )->toString(),
        '@migrate_tools_link' => Link::fromTextAndUrl(
          'run the import', Url::fromRoute('entity.migration_group.list')
        )->toString(),
      ]),
    ];
    $form['help'] = [
      '#type' => 'item',
      '#markup' => '<p>To import the most recent stories from <a href="https://news.arizona.edu" target="_blank">news.arizona.edu</a> regardless of tag, select "All".</p>' .
      '<p>Deselect "All" if you want to import the most recent stories of any specific tag or tags.</p>' .
      '<p>If you select multiple tags, this will import stories with any of the selected tags, and not just stories with all of the selected tags.</p>' .
      '<p>This importer will create taxonomy terms from the selected tags, if they exist on a story in the feed.</p>',
    ];
    $form['term_options'] = [
      '#type' => 'value',
      '#value' => $term_options,
    ];
    $form['uarizona_news_terms'] = [
      '#title' => t('News Categories'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#description' => 'Select which terms you want to import.',
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

    // Update the news.arizona.edu feed url in the migration group config.
    $group_config = $this->configFactory->getEditable('migrate_plus.migration_group.az_news_feeds');
    $base_uri = $az_news_feeds_config->get('uarizona_news_base_uri');
    $content_path = $az_news_feeds_config->get('uarizona_news_content_path');
    $selected_terms = $az_news_feeds_config->get('uarizona_news_terms');
    $views_contextual_argument = implode('+', array_keys($selected_terms));
    $urls = $base_uri . $content_path . '/' . $views_contextual_argument;
    $group_config
      ->set('shared_configuration.source.urls', $urls)
      ->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
