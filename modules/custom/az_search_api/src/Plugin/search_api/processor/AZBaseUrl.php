<?php

namespace Drupal\az_search_api\Plugin\search_api\processor;

use Drupal\Core\State\StateInterface;
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
  protected $baseUrl;

  /**
   * The xml_base_url.
   *
   * @var string
   */
  protected $xmlBaseUrl;

  /**
   * The state service.
   */
  protected ?StateInterface $state = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    // Compute the baseUrl from front page;
    // Cannot use the request object here, there may be no request.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    $processor->baseUrl = $url->toString();

    // Get the state service.
    $processor->setState($container->get('state'));
    return $processor;
  }

  /**
   * Retrieves state service.
   *
   * @return \Drupal\Core\State\StateInterface
   *   The state service.
   */
  public function getState(): StateInterface {
    return $this->state ?: \Drupal::state();
  }

  /**
   * Sets the state service.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   *
   * @return $this
   */
  public function setState(StateInterface $state): static {
    $this->state = $state;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // @todo should xml_base be set once in the constructor?
    $xml_base = $this->getState()->get('xmlsitemap_base_url');
    if (!empty($xml_base)) {
      // Append a trailing slash if there isn't one.
      if (!str_ends_with($xml_base, '/')) {
        $xml_base .= '/';
      }
      $value = str_replace($this->baseUrl, $xml_base, $value);
    }
  }

}
