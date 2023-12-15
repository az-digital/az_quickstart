<?php

declare(strict_types=1);

namespace Drupal\az_publication;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Quickstart Citation Style entities.
 *
 * @see Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class AZQuickstartCitationStyleHtmlRouteProvider extends AdminHtmlRouteProvider {
  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Define the 'enable' route
    $route = new Route('/admin/config/az-quickstart/settings/az-publication/{az_publication_type}/enable');
    $route->setDefault('_controller', '\Drupal\az_publication\Controller\AZPublicationTypeController::ajaxOperation');
    $route->setDefault('op', 'enable');
    $route->setRequirement('_entity_access', 'az_publication_type.enable');
    $route->setRequirement('_csrf_token', 'TRUE');
    $collection->add('entity.az_publication_type.enable', $route);

    // Define the 'disable' route
    $route = new Route('/admin/config/az-quickstart/settings/az-publication/{az_publication_type}/disable');
    $route->setDefault('_controller', '\Drupal\az_publication\Controller\AZPublicationTypeController::ajaxOperation');
    $route->setDefault('op', 'disable');
    $route->setRequirement('_entity_access', 'az_publication_type.disable');
    $route->setRequirement('_csrf_token', 'TRUE');
    $collection->add('entity.az_publication_type.disable', $route);

    return $collection;
  }

}
