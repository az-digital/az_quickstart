<?php

namespace Drupal\workbench_access\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * An interface for Section Association entity.
 *
 * Section Association entities track the assignment of section data.
 * They are not directly editable.
 *
 * @internal
 */
interface SectionAssociationInterface extends ContentEntityInterface {

  /**
   * Returns the scheme id for the Section Association.
   */
  public function getSchemeId();

  /**
   * Returns an array of currently assigned user ids for the section.
   *
   * @return array
   *   A positional array of user ids.
   */
  public function getCurrentUserIds();

  /**
   * Returns an array of currently assigned role ids for the section.
   *
   * @return array
   *   A positional array of role ids.
   */
  public function getCurrentRoleIds();

}
