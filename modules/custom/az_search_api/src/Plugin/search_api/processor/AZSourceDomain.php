<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\az_search_api\Plugin\search_api\processor\Property\AZDomainProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a domain from the xmlsitemap base.
 */
#[SearchApiProcessor(
  id: 'az_source_domain',
  label: new TranslatableMarkup('XML Sitemap Domain'),
  description: new TranslatableMarkup("Retrieves the base domain for Search API."),
  stages: [
    'add_properties' => 0,
  ],
  locked: TRUE,
  hidden: TRUE,
)]
class AZSourceDomain extends ProcessorPluginBase {

  /**
   * The base_url.
   *
   * @var string
   */
  protected ?string $baseDomain = NULL;

  /**
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    // \Drupal\Core\State\StateInterface $state;
    $processor->state = $container->get('state');
    return $processor;
  }

  /**
   * Retrieves the XML sitemap base domain.
   *
   * @return string|null
   *   Domain of the XML sitemap base, without protocol or path.
   */
  public function getDomain(): ?string {
    // Extract the domain if we haven't yet.
    if (empty($this->baseDomain)) {
      // Get the xmlsitemap base.
      $baseUrl = $this->state->get('xmlsitemap_base_url');
      if (!empty($baseUrl)) {
        // Parse the URL to get just the domain.
        $domain = parse_url($baseUrl, PHP_URL_HOST);
        if (!empty($domain)) {
          $this->baseDomain = $domain;
        }
      }
    }
    return $this->baseDomain;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(?DatasourceInterface $datasource = NULL) {
    $properties = [];
    if (!$datasource) {
      $definition = [
        'label' => $this->t('XML Sitemap Base Domain'),
        'description' => $this->t('The base domain retrieved from the XML Sitemap.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['az_source_domain'] = new AZDomainProperty($definition);
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $fields = $item->getFields(FALSE);
    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($fields, NULL, 'az_source_domain');

    $domain = $this->getDomain();
    if (!empty($domain)) {
      foreach ($fields as $field) {
        $field->addValue($domain);
      }
    }
  }

}
