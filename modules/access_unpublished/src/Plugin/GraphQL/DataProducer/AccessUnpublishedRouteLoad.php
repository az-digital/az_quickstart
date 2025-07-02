<?php

namespace Drupal\access_unpublished\Plugin\GraphQL\DataProducer;

use Drupal\access_unpublished\TokenGetter;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\Routing\RouteLoad;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Loads a route with an optional token.
 *
 * @DataProducer(
 *   id = "access_unpublished_route_load",
 *   name = @Translation("Load route with token"),
 *   description = @Translation("Loads a route with an optional token."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Route")
 *   ),
 *   consumes = {
 *     "path" = @ContextDefinition("string",
 *       label = @Translation("Path")
 *     ),
 *     "token" = @ContextDefinition("string",
 *       label = @Translation("Token"),
 *       required = FALSE
 *     )
 *   }
 * )
 *
 * @deprecated in access_unpublished:8.x-1.3 and is removed from access_unpublished:2.0.0.
 *   Use Drupal\access_unpublished\Plugin\GraphQL\DataProducer\AccessUnpublishedTokenSet
 *   instead and compose it in your field resolver.
 * @see https://www.drupal.org/project/access_unpublished/issues/3217330
 */
class AccessUnpublishedRouteLoad extends RouteLoad {

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
    $routeLoad = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $routeLoad->setTokenGetter($container->get('access_unpublished.token_getter'));
    return $routeLoad;
  }

  /**
   * Sets the token getter service.
   *
   * @param \Drupal\access_unpublished\TokenGetter $tokenGetter
   *   The token getter service.
   */
  protected function setTokenGetter(TokenGetter $tokenGetter) {
    $this->tokenGetter = $tokenGetter;
  }

  /**
   * {@inheritdoc}
   */
  public function resolveUnpublished($path, $token, RefinableCacheableDependencyInterface $metadata) {
    $this->tokenGetter->setToken($token);
    return parent::resolve($path, $metadata);
  }

  /**
   * {@inheritdoc}
   */
  public function resolveField(FieldContext $field) {
    $context = $this->getContextValues();
    return call_user_func_array(
      [$this, 'resolveUnpublished'],
      array_values(array_merge($context, [$field]))
    );
  }

}
