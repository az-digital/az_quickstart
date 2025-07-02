<?php

namespace Drupal\flag;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * The interface for flagging entities.
 */
interface FlaggingInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Gets the flag ID for the parent flag.
   *
   * @return string
   *   The flag ID.
   */
  public function getFlagId();

  /**
   * Returns the parent flag entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\flag\FlagInterface
   *   The flag related to this flagging.
   */
  public function getFlag();

  /**
   * Returns the entity that is flagged by this flagging.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity object.
   */
  public function getFlaggable();

  /**
   * Returns the type of entity flagged by this flagging (e.g., 'node').
   *
   * @return string
   *   A string containing the flaggable type ID.
   */
  public function getFlaggableType();

  /**
   * Returns the time that the flagging was created.
   *
   * @return int
   *   The timestamp of when the flagging was created.
   */
  public function getCreatedTime();

  /**
   * Returns the ID of the entity that is flagged by this flagging.
   *
   * @return string
   *   A string containing the flaggable ID.
   */
  public function getFlaggableId();

}
