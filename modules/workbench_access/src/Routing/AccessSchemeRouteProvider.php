<?php

namespace Drupal\workbench_access\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\workbench_access\Controller\WorkbenchAccessSections;
use Symfony\Component\Routing\Route;

/**
 * Defines a route provider for access schemes.
 */
class AccessSchemeRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $routes = parent::getRoutes($entity_type);
    if ($entity_type->hasLinkTemplate('sections')) {
      $route = new Route($entity_type->getLinkTemplate('sections'));
      $route->setDefault('_controller', WorkbenchAccessSections::class . '::page');
      $route->setDefault('_title', 'Sections');
      $route->setRequirement('_permission', 'assign workbench access');
      $route->setOption('parameters', [
        'access_scheme' => ['type' => 'entity:access_scheme'],
      ]);
      $routes->add('entity.access_scheme.sections', $route);
    }
    return $routes;
  }

}
