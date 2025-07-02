<?php

namespace Drupal\workbench_access;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines an interface handling Workbench Access configuration.
 */
interface WorkbenchAccessManagerInterface extends PluginManagerInterface {
  // @todo Remove.
  const FIELD_NAME = 'field_workbench_access';

  /**
   * Checks that an entity belongs to a user section or its children.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param array $entity_sections
   *   The section assignments for the entity. An array of section ids.
   * @param array $user_sections
   *   The section assignments for the user. An array of section ids.
   *
   *   return boolean.
   */
  public static function checkTree(AccessSchemeInterface $scheme, array $entity_sections, array $user_sections);

  /**
   * Returns a flat array of all active section ids.
   *
   * Used to display assignments for admins.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param bool $root_only
   *   If TRUE, only show the root-level assignments.
   *
   * @return array
   *   All sections for given scheme.
   */
  public static function getAllSections(AccessSchemeInterface $scheme, $root_only = FALSE);

  /**
   * Determines if a user is assigned to all sections.
   *
   * This method checks the permissions and assignments for a user. Someone set
   * as an admin or with access to the top-level sections is assumed to be able
   * to access all sections. We use this logic in query filtering.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Account being checked. If not provided, the active user is used.
   *
   * @return bool
   *   TRUE if user is in all schemes.
   */
  public function userInAll(AccessSchemeInterface $scheme, ?AccountInterface $account = NULL);

}
