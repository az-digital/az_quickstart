<?php

namespace Drupal\auto_entitylabel\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\EnhancerInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * RouteEnhancer Class.
 *
 * Enhances Auto Entity Label routes by adding proper information about the
 * bundle name.
 */
class RouteEnhancer implements EnhancerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteEnhancer object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function enhance(array $defaults, Request $request) {
    if (!$this->applies($defaults[RouteObjectInterface::ROUTE_OBJECT])) {
      return $defaults;
    }

    if (($bundle = $this->entityTypeManager->getDefinition($defaults['entity_type_id'])->getBundleEntityType()) && isset($defaults[$bundle])) {
      // Auto Entity Label forms only need the actual name of the bundle they're
      // dealing with, not an upcasted entity object, so provide a simple way
      // for them to get it.
      $defaults['bundle'] = $defaults['_raw_variables']->get($bundle);
    }

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(Route $route) {
    return ($route->hasOption('auto_label'));
  }

}
