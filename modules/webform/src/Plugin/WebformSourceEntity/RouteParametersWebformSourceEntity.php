<?php

namespace Drupal\webform\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\Plugin\WebformSourceEntityBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Detect source entity by examining route parameters.
 *
 * @WebformSourceEntity(
 *   id = "route_parameters",
 *   label = @Translation("Route parameters"),
 *   weight = 100
 * )
 */
class RouteParametersWebformSourceEntity extends WebformSourceEntityBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity(array $ignored_types) {
    // Use current account when viewing a user's submissions.
    // @see \Drupal\webform\WebformSubmissionListBuilder
    if ($this->routeMatch->getRouteName() === 'entity.webform_submission.user') {
      return NULL;
    }

    // Get the most specific source entity available in the current route's
    // parameters.
    $parameters = $this->routeMatch->getParameters()->all();
    $parameters = array_reverse($parameters);

    if (!empty($ignored_types)) {
      $parameters = array_diff_key($parameters, array_flip($ignored_types));
    }

    foreach ($parameters as $value) {
      if ($value instanceof EntityInterface) {
        return $value;
      }
    }
    return NULL;
  }

}
