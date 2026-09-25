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
  id: 'az_front_page',
  label: new TranslatableMarkup('Transform Front Page URL'),
  description: new TranslatableMarkup('Transform the url alias of the front page'),
  stages: [
    'pre_index_save' => 0,
    'preprocess_index' => -20,
    'preprocess_query' => -20,
  ],
)]
class AZFrontPage extends FieldsProcessorPluginBase {

  /**
   * The base_url.
   *
   * @var string
   */
  protected ?string $baseUrl;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface the config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\path_alias\AliasManagerInterface the alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $processor->configFactory = $container->get('config.factory');
    $processor->aliasManager = $container->get('path_alias.manager');
    return $processor;
  }

  /**
   * {@inheritdoc}
   */
  protected function process(&$value) {
    // Compute the true current base URL.
    // This can vary, e.g. drush, other environments.
    // Cannot use the request object here, there may be no request.
    $url = Url::fromRoute('<front>', [], ['absolute' => TRUE]);
    $url = $url->toString();
    // Get the front page setting.
    $front = $this->configFactory->get('system.site')->get('page.front');
    if (!empty($front)) {
      try {
        // Attempt to get the alias of the front page.
        $alias = $this->aliasManager->getAliasByPath($front);
        if (!empty($alias)) {
          // Use the alias if we found one.
          $front = $alias;
        }
        // Compute what drupal thinks the path of the front page is.
        // front might be /my/url/alias, or /node/1, for example.
        $alias_url = URL::fromURI('internal:' . $front, ['absolute' => TRUE]);
        $alias_url = $alias_url->toString();
        if ($alias_url === $value) {
          // If the processed value is the alias, use the base url instead.
          $value = $url;
        }
      }
      catch (\InvalidArgumentException $e) {
        // Leave URL alone, as we failed to get the front page URL alias.
      }
    }
  }

}
