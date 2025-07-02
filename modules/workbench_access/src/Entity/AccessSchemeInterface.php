<?php

namespace Drupal\workbench_access\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Access scheme entities.
 */
interface AccessSchemeInterface extends ConfigEntityInterface {

  /**
   * Gets the scheme plural label.
   *
   * @return string
   *   Scheme label.
   */
  public function getPluralLabel();

  /**
   * Gets the access scheme for this configuration entity.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface
   *   Gets the access scheme.
   */
  public function getAccessScheme();

}
