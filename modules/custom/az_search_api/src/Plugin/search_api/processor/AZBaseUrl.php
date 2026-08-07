<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\search_api\Attribute\SearchApiProcessor;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Transforms base_url into a value matching xmlsitemap.
 */
#[SearchApiProcessor(
  id: 'az_base_url',
  label: new TranslatableMarkup('Transform Base URL'),
  description: new TranslatableMarkup('Transform the base url with the XML sitemap default'),
  stages: [
    'pre_index_save' => 0,
    'preprocess_index' => -10,
    'preprocess_query' => -10,
  ],
)]
class AZBaseUrl extends FieldsProcessorPluginBase {

  /**
   * The base_url.
   *
   * @var string
   */
  protected ?string $baseUrl;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    // \Drupal\Core\State\StateInterface $state;
    $state = $container->get('state');
    $baseUrl = $state->get('xmlsitemap_base_url');
    if (!empty($baseUrl)) {
      // Append a trailing slash if there isn't one.
      if (!str_ends_with($baseUrl, '/')) {
        $baseUrl .= '/';
      }
      $processor->baseUrl = $baseUrl;
    }

    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // Compute the current base URL.
    // This can vary, e.g. drush, other environments.
    // Cannot use the request object here, there may be no request.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    $url = $url->toString();

    if (!empty($this->baseUrl)) {
      $value = str_replace($url, $this->baseUrl, $value);
    }
  }

}
