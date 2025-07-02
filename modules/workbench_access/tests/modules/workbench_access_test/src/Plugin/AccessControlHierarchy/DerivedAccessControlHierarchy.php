<?php

namespace Drupal\workbench_access_test\Plugin\AccessControlHierarchy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\workbench_access\AccessControlHierarchyBase;

/**
 * Defines a hierarchy based on an entity hierarchy field.
 *
 * @AccessControlHierarchy(
 *   id = "workbench_access_test_derived",
 *   module = "workbench_access_test",
 *   deriver = "Drupal\workbench_access_test\Plugin\Derivative\DerivedAccessControlPlugins",
 *   label = @Translation("Derived plugins"),
 *   description = @Translation("Uses derivatives for plugins.")
 * )
 */
class DerivedAccessControlHierarchy extends AccessControlHierarchyBase {

  /**
   * {@inheritdoc}
   */
  public function applies($entity_type_id, $bundle) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityValues(EntityInterface $entity) {
    return [];
  }

}
