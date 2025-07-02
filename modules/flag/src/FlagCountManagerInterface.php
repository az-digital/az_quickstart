<?php

namespace Drupal\flag;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Flag Count Manager Interface.
 */
interface FlagCountManagerInterface {

  /**
   * Gets flag counts for all flags on an entity.
   *
   * Provides a count of all the flaggings for a single entity. Instead
   * of a single response, this method returns an array of counts keyed by
   * the flag ID:
   *
   * @code
   * [
   *   my_flag => 42
   *   another_flag => 57
   * ];
   * @endcode
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   An array giving the counts of all flaggings on the entity. The flag IDs
   *   are the keys and the counts for each flag the values. Note that flags
   *   that have no flaggings are not included in the array.
   */
  public function getEntityFlagCounts(EntityInterface $entity);

  /**
   * Gets the count of flaggings for the given flag.
   *
   * For example, if you have an 'endorse' flag, this method will tell you how
   * many endorsements have been made, rather than how many things have been
   * endorsed.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag.
   *
   * @return int
   *   The number of flaggings for the flag.
   */
  public function getFlagFlaggingCount(FlagInterface $flag);

  /**
   * Gets the count of entities flagged by the given flag.
   *
   * For example, with a 'report abuse' flag, this returns the number of
   * entities that have been reported, not the total number of reports. In other
   * words, an entity that has been reported multiple times will only be counted
   * once.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag for which to retrieve a flag count.
   *
   * @return int
   *   The number of entities that are flagged with the flag.
   */
  public function getFlagEntityCount(FlagInterface $flag);

  /**
   * Gets the count of the flaggings made by a user with a flag.
   *
   * For example, with a 'bookmarks' flag, this returns the number of bookmarks
   * a user has created.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag.
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The account.
   * @param string $session_id
   *   (optional) The session ID used to specify a unique anonymous user.
   *
   * @return int
   *   The number of flaggings for the given flag and user.
   *
   * @throws \LogicException
   *   Throws an exception if $account is the anonymous user but $session_id is
   *   NULL.
   */
  public function getUserFlagFlaggingCount(FlagInterface $flag, AccountInterface $user, $session_id = NULL);

}
