<?php

namespace Drupal\access_unpublished\Routing;

use Drupal\access_unpublished\Controller\AccessTokenController;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Contains routs for access tokens.
 */
class AccessTokenRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();
    $entity_type_id = $entity_type->id();

    if ($add_page_route = $this->getRenewRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.renew", $add_page_route);
    }
    if ($add_page_route = $this->getDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.delete", $add_page_route);
    }

    return $collection;
  }

  /**
   * Gets the add page route.
   *
   * Built only for entity types that have bundles.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRenewRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('renew'));
    $route->setDefault('_controller', AccessTokenController::class . '::renew')
      ->setRequirement('_permission', 'renew token')
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);
    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route($entity_type->getLinkTemplate('delete'));
    $route->setDefault('_controller', AccessTokenController::class . '::delete')
      ->setRequirement('_permission', 'delete token')
      ->setOption('parameters', [
        $entity_type_id => ['type' => 'entity:' . $entity_type_id],
      ]);
    return $route;
  }

}
