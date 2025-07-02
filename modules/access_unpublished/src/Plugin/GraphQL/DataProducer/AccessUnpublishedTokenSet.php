<?php

namespace Drupal\access_unpublished\Plugin\GraphQL\DataProducer;

use Drupal\access_unpublished\TokenGetter;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Set an access_unpublished token.
 *
 * @DataProducer(
 *   id = "access_unpublished_token_set",
 *   name = @Translation("Set an access_unpublished token"),
 *   description = @Translation("Set an access_unpublished token."),
 *   consumes = {
 *     "token" = @ContextDefinition("string",
 *       label = @Translation("Token"),
 *       required = FALSE
 *     )
 *   }
 * )
 */
class AccessUnpublishedTokenSet extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The token getter service.
   *
   * @var \Drupal\access_unpublished\TokenGetter
   */
  protected $tokenGetter;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_unpublished.token_getter')
    );
  }

  /**
   * AccessUnpublishedTokenSet constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $pluginId
   *   The plugin id.
   * @param mixed $pluginDefinition
   *   The plugin definition.
   * @param \Drupal\access_unpublished\TokenGetter $tokenGetter
   *   The token getter service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    TokenGetter $tokenGetter
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->tokenGetter = $tokenGetter;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve($token, RefinableCacheableDependencyInterface $metadata) {
    $this->tokenGetter->setToken($token);
  }

}
