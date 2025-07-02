<?php

declare(strict_types=1);

namespace Drupal\google_tag_test\Plugin\GoogleTag\Event;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Route name event plugn.
 *
 * @GoogleTagEvent(
 *   id= "route_name",
 *   event_name = "route_name",
 *   label = @Translation("Adds an event which contains the route name")
 * )
 */
final class RouteNameEvent extends EventBase implements ContainerFactoryPluginInterface {

  /**
   * Route matcher.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    return [
      'route_name' => $this->routeMatch->getRouteName(),
    ];
  }

}
