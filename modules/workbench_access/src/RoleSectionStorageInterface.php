<?php

namespace Drupal\workbench_access;

use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines an interface for storing and retrieving sections for a role.
 */
interface RoleSectionStorageInterface {

  /**
   * Adds a set of sections to a role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   A role id.
   * @param array $sections
   *   An array of section ids to assign to this role.
   */
  public function addRole(AccessSchemeInterface $scheme, $role_id, array $sections = []);

  /**
   * Removes a set of sections from a role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $role_id
   *   A role id.
   * @param array $sections
   *   An array of section ids to remove from this role.
   */
  public function removeRole(AccessSchemeInterface $scheme, $role_id, array $sections = []);

  /**
   * Gets a list of potential roles.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of roles keyed by rid with name values.
   */
  public function getPotentialRoles($id);

  /**
   * Gets a list of potential roles for assigning users.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of roles keyed by rid with rid values.
   */
  public function getPotentialRolesFiltered($id);

  /**
   * Gets a list of roles assigned to a section.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of role ids
   */
  public function getRoles(AccessSchemeInterface $scheme, $id);

  /**
   * Gets the sections assigned to a user by way of their roles.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to retrieve sections for by way of their roles.
   *
   * @return array
   *   Array of section IDs.
   */
  public function getRoleSections(AccessSchemeInterface $scheme, ?AccountInterface $account);

}
