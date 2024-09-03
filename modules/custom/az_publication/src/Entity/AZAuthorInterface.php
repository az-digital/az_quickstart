<?php

namespace Drupal\az_publication\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Author entities.
 *
 * @ingroup az_publication
 */
interface AZAuthorInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Author name.
   *
   * @return string
   *   Name of the Author.
   */
  public function getName();

  /**
   * Sets the Author name.
   *
   * @param string $name
   *   The Author name.
   *
   * @return \Drupal\az_publication\Entity\AZAuthorInterface
   *   The called Author entity.
   */
  public function setName($name);

  /**
   * Gets the Author creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Author.
   */
  public function getCreatedTime();

  /**
   * Sets the Author creation timestamp.
   *
   * @param int $timestamp
   *   The Author creation timestamp.
   *
   * @return \Drupal\az_publication\Entity\AZAuthorInterface
   *   The called Author entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Gets the Author revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Author revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\az_publication\Entity\AZAuthorInterface
   *   The called Author entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Author revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Author revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\az_publication\Entity\AZAuthorInterface
   *   The called Author entity.
   */
  public function setRevisionUserId($uid);

}
