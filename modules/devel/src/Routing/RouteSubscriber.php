<?php

namespace Drupal\devel\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\devel\Controller\EntityDebugController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Devel routes.
 *
 * @see \Drupal\devel\Controller\EntityDebugController
 * @see \Drupal\devel\Plugin\Derivative\DevelLocalTask
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The router service.
   */
  protected RouteProviderInterface $routeProvider;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $router_provider
   *   The router service.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, RouteProviderInterface $router_provider) {
    $this->entityTypeManager = $entity_manager;
    $this->routeProvider = $router_provider;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $route = $this->getEntityLoadRoute($entity_type);
      if ($route instanceof Route) {
        $collection->add(sprintf('entity.%s.devel_load', $entity_type_id), $route);
      }

      $route = $this->getEntityLoadWithReferencesRoute($entity_type);
      if ($route instanceof Route) {
        $collection->add(sprintf('entity.%s.devel_load_with_references', $entity_type_id), $route);
      }

      $route = $this->getEntityRenderRoute($entity_type);
      if ($route instanceof Route) {
        $collection->add(sprintf('entity.%s.devel_render', $entity_type_id), $route);
      }

      $route = $this->getEntityTypeDefinitionRoute($entity_type);
      if ($route instanceof Route) {
        $collection->add(sprintf('entity.%s.devel_definition', $entity_type_id), $route);
      }

      $route = $this->getPathAliasesRoute($entity_type);
      if ($route instanceof Route) {
        $collection->add(sprintf('entity.%s.devel_path_alias', $entity_type_id), $route);
      }
    }
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityLoadRoute(EntityTypeInterface $entity_type): ?Route {
    if ($devel_load = $entity_type->getLinkTemplate('devel-load')) {
      $route = (new Route($devel_load))
        ->addDefaults([
          '_controller' => EntityDebugController::class . '::entityLoad',
          '_title' => 'Devel Load',
        ])
        ->addRequirements([
          '_permission' => 'access devel information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_devel_entity_type_id', $entity_type->id());

      // Set the parameters of the new route using the existing 'edit-form'
      // route parameters. If there are none (for example, where Devel creates
      // a link for entities with no edit-form) then we need to set the basic
      // parameter [entity_type_id => [type => 'entity:entity_type_id']].
      // @see https://gitlab.com/drupalspoons/devel/-/issues/377
      $parameters = $this->getRouteParameters($entity_type, 'edit-form') !== [] ? $this->getRouteParameters($entity_type, 'edit-form') : [$entity_type->id() => ['type' => 'entity:' . $entity_type->id()]];
      $route->setOption('parameters', $parameters);

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the entity load route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityLoadWithReferencesRoute(EntityTypeInterface $entity_type): Route|null {
    $devel_load = $entity_type->getLinkTemplate('devel-load-with-references');
    if ($devel_load === FALSE) {
      return NULL;
    }

    $entity_type_id = $entity_type->id();
    $route = new Route($devel_load);
    $route
      ->addDefaults([
        '_controller' => EntityDebugController::class . '::entityLoadWithReferences',
        '_title' => 'Devel Load (with references)',
      ])
      ->addRequirements([
        '_permission' => 'access devel information',
      ])
      ->setOption('_admin_route', TRUE)
      ->setOption('_devel_entity_type_id', $entity_type_id)
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);

    return $route;
  }

  /**
   * Gets the entity render route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityRenderRoute(EntityTypeInterface $entity_type): ?Route {
    if ($devel_render = $entity_type->getLinkTemplate('devel-render')) {
      $route = (new Route($devel_render))
        ->addDefaults([
          '_controller' => EntityDebugController::class . '::entityRender',
          '_title' => 'Devel Render',
        ])
        ->addRequirements([
          '_permission' => 'access devel information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_devel_entity_type_id', $entity_type->id());

      if (($parameters = $this->getRouteParameters($entity_type, 'canonical')) !== []) {
        $route->setOption('parameters', $parameters);
      }

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the entity type definition route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityTypeDefinitionRoute(EntityTypeInterface $entity_type): ?Route {
    if ($devel_definition = $entity_type->getLinkTemplate('devel-definition')) {
      $route = (new Route($devel_definition))
        ->addDefaults([
          '_controller' => EntityDebugController::class . '::entityTypeDefinition',
          '_title' => 'Entity type definition',
        ])
        ->addRequirements([
          '_permission' => 'access devel information',
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_devel_entity_type_id', $entity_type->id());

      $link_template = $entity_type->getLinkTemplate('edit-form') ? 'edit-form' : 'canonical';
      if (($parameters = $this->getRouteParameters($entity_type, $link_template)) !== []) {
        $route->setOption('parameters', $parameters);
      }

      return $route;
    }

    return NULL;
  }

  /**
   * Gets the path aliases route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getPathAliasesRoute(EntityTypeInterface $entity_type): ?Route {
    $path_alias_definition = $entity_type->getLinkTemplate('devel-path-alias');
    if ($path_alias_definition === FALSE) {
      return NULL;
    }

    $route = new Route($path_alias_definition);
    $route
      ->addDefaults([
        '_controller' => EntityDebugController::class . '::pathAliases',
        '_title' => 'Path aliases',
      ])
      ->addRequirements([
        '_permission' => 'access devel information',
      ])
      ->setOption('_admin_route', TRUE)
      ->setOption('_devel_entity_type_id', $entity_type->id());

    $link_template = $entity_type->getLinkTemplate('edit-form') ? 'edit-form' : 'canonical';
    $parameters = $this->getRouteParameters($entity_type, $link_template);
    if ($parameters !== []) {
      $route->setOption('parameters', $parameters);
    }

    return $route;
  }

  /**
   * Gets the route parameters from the template.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param string $link_template
   *   The link template.
   *
   * @return array[]
   *   A list of route of parameters.
   */
  protected function getRouteParameters(EntityTypeInterface $entity_type, string $link_template): array {
    $parameters = [];
    if (!$path = $entity_type->getLinkTemplate($link_template)) {
      return $parameters;
    }

    $original_route_parameters = [];
    $candidate_routes = $this->routeProvider->getRoutesByPattern($path);
    if ($candidate_routes->count()) {
      // Guess the best match. There could be more than one route sharing the
      // same path. Try first an educated guess based on the route name. If we
      // can't find one, pick-up the first from the list.
      $name = 'entity.' . $entity_type->id() . '.' . str_replace('-', '_', $link_template);
      if (!$original_route = $candidate_routes->get($name)) {
        $iterator = $candidate_routes->getIterator();
        $iterator->rewind();
        $original_route = $iterator->current();
      }

      $original_route_parameters = $original_route->getOption('parameters') ?? [];
    }

    if (preg_match_all('/{\w*}/', $path, $matches)) {
      foreach ($matches[0] as $match) {
        $match = str_replace(['{', '}'], '', $match);
        // This match has an original route parameter definition.
        if (isset($original_route_parameters[$match])) {
          $parameters[$match] = $original_route_parameters[$match];
        }
        // It could be an entity type?
        elseif ($this->entityTypeManager->hasDefinition($match)) {
          $parameters[$match] = ['type' => 'entity:' . $match];
        }
      }
    }

    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

}
