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
    return [];
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
    $form['help_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Help'),
      '#open' => TRUE,
    ];

    $taxonomy_url = Url::fromUri('internal:/admin/structure/taxonomy/manage/az_news_tags/overview');
    $taxonomy_link = Link::fromTextAndUrl('News Tags vocabulary', $taxonomy_url)->toString();

    $markup = '<p>To import the most recent stories from <a href="https://news.arizona.edu" target="_blank">news.arizona.edu</a> regardless of tag, select "All".</p>' .
        '<p>Deselect "All" if you want to import the most recent stories of any specific tag or tags.</p>' .
        '<p>If you select multiple tags, this will import stories with any of the selected tags, and not just stories with all of the selected tags.</p>' .
        '<p>This importer associates stories with existing taxonomy terms based on tags from the feed. If a story in the feed includes tags, the importer will check if these tags correspond to any existing terms in the ' . $taxonomy_link . ' on the site. It will then associate the story with those existing terms. For instance, if a story\'s tags include \'Lunar and Planetary Laboratory\' and this term exists in the ' . $taxonomy_link . ' on the site, the importer will add this term to the story. Tags that do not match any existing taxonomy terms on the site will be ignored and not added to the story.</p>';

    $form['help_container']['help'] = [
      '#type' => 'item',
      '#markup' => $markup,
    ];

    $form['term_options'] = [
      '#type' => 'value',
      '#value' => $term_options,
    ];

    $form['group_configuration_hidden'] = [
      '#type' => 'hidden',
      '#config_target' => 'migrate_plus.migration_group.az_news_feeds:shared_configuration.source.urls',
    ];

    $group_config = $this->config('migrate_plus.migration_group.az_news_feeds');
    $default_url = $group_config->get('shared_configuration.source.urls');
    $url = Url::fromUri($default_url);

    $link_render_array = [
      '#type' => 'link',
      '#title' => $this->t('@url', ['@url' => $url->toString()]),
      '#url' => $url,
    ];

    $form['group_configuration'] = [
      '#type' => 'container',
      '#attributes' => ['id' => ['endpoint-wrapper']],
      'text' => [
        '#markup' => $this->t('Fetch news from: '),
      ],
      'link' => $link_render_array,
    ];
    $form['uarizona_news_terms'] = [
      '#title' => $this->t('News Categories'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#description' => $this->t('Select which terms you want to import.'),
      '#options' => $form['term_options']['#value'],
      '#config_target' => 'az_news_feeds.settings:uarizona_news_terms',
      '#ajax' => [
        'callback' => '::updateEndpointCallback',
        'wrapper' => 'endpoint-wrapper',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Updates the endpoint URL based on selected terms and updates form elements.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   *
   * @return array
   *   An array of updated form elements.
   */
  public function updateEndpointCallback(array &$form, FormStateInterface $form_state) {
    $az_news_feeds_config = $this->config('az_news_feeds.settings');
    // Generate the new endpoint URL based on the selected terms.
    $selected_terms = $form_state->getValue('uarizona_news_terms');
    $base_uri = $az_news_feeds_config->get('uarizona_news_base_uri');
    $content_path = $az_news_feeds_config->get('uarizona_news_content_path');
    if (!$selected_terms) {
      $selected_terms = ['all' => 'All'];
    }
    $views_contextual_argument = implode('+', array_keys($selected_terms));
    $new_endpoint_url = $base_uri . $content_path . $views_contextual_argument;
    $url = Url::fromUri($new_endpoint_url);
    $link_render_array = [
      '#type' => 'link',
      '#title' => $this->t('@url', ['@url' => $url->toString()]),
      '#url' => $url,
    ];

    // Update the hidden field's value.
    $form['group_configuration_hidden']['#value'] = $new_endpoint_url;
    // Update the markup field's display.
    $form['group_configuration']['link'] = $link_render_array;
    return [
      $form['group_configuration_hidden'],
      $form['group_configuration'],
    ];

  }

}
