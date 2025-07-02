<?php

namespace Drupal\workbench_access;

use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;

/**
 * Defines an interface for storing and retrieving sections for a user.
 */
interface UserSectionStorageInterface {

  /**
   * Adds a set of sections to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to add to the section.
   * @param array $sections
   *   An array of section ids to assign to this user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The saved user entity.
   */
  public function addUser(AccessSchemeInterface $scheme, AccountInterface $account, array $sections = []);

  /**
   * Removes a set of sections to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User to remove from the section.
   * @param array $sections
   *   An array of section ids to assign to this user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The saved user entity.
   */
  public function removeUser(AccessSchemeInterface $scheme, AccountInterface $account, array $sections = []);

  /**
   * Gets a list of editors assigned to a section.
   *
   * This method does not return editors assigned by role.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array keyed user ids with values of user labels (names).
   */
  public function getEditors(AccessSchemeInterface $scheme, $id);

  /**
   * Gets a list of editors who may be assigned to a section.
   *
   * This method does not remove editors already assigned to a section.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of user ids.
   */
  public function getPotentialEditors($id);

  /**
   * Gets a list of editors roles who may be assigned to a section.
   *
   * @param string $id
   *   The section id.
   *
   * @return array
   *   An array of role ids.
   */
  public function getPotentialEditorsRoles($id);

  /**
   * Gets the editorial sections assigned to a user.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   An optional user. If not provided, the active user is returned.
   * @param bool $add_roles
   *   Whether to add the role-based assignments to the user. Defaults to true.
   *
   * @return array
   *   An array of section ids that the user is assigned to.
   */
  public function getUserSections(AccessSchemeInterface $scheme, ?AccountInterface $account = NULL, $add_roles = TRUE);

}
