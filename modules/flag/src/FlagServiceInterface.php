<?php

namespace Drupal\flag;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Flag service interface.
 */
interface FlagServiceInterface {

  /**
   * List all flags available.
   *
   * For example to list all flags operating on articles:
   *
   * @code
   *   $this->flagService->getAllFlags('node', 'article');
   * @endcode
   *
   * If all the parameters are omitted, a list of all flags will be returned.
   *
   * Note that this does not check for any kind of access.
   *
   * @param string $entity_type
   *   (optional) The type of entity for which to load the flags.
   * @param string $bundle
   *   (optional) The bundle for which to load the flags.
   *
   * @return \Drupal\flag\FlagInterface[]
   *   An array of flag entities, keyed by the entity IDs.
   */
  public function getAllFlags($entity_type = NULL, $bundle = NULL);

  /**
   * Get a single flagging for given a flag and  entity.
   *
   * Use this method to check if a given entity is flagged or not.
   *
   * For example, to get a bookmark flagging for a node:
   *
   * @code
   *   $flag = \Drupal::service('flag')->getFlagById('bookmark');
   *   $node = Node::load($node_id);
   *   $flagging = \Drupal::service('flag')->getFlagging($flag, $node);
   * @endcode
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account of the flagging user. If omitted, the flagging for
   *   the current user will be returned.
   * @param string $session_id
   *   (optional) The session ID. If omitted and the current user is anonymous
   *    the current session id will be used to uniquely identify the anonymous
   *    user.
   *
   * @return \Drupal\flag\FlaggingInterface|null
   *   The flagging or NULL if the flagging is not found.
   *
   * @throws \LogicException
   *   Thrown when $account is anonymous but no associated session ID is
   *   specified.
   *
   * @see \Drupal\flag\FlagServiceInterface::getFlaggings()
   */
  public function getFlagging(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL);

  /**
   * Returns the current anonymous flag session ID.
   *
   * @return string|null
   *   The session ID or NULL not an anonymous user.
   */
  public function getAnonymousSessionId();

  /**
   * Get flaggings for the given entity, flag, and optionally, user.
   *
   * This method works very much like FlagServiceInterface::getFlagging() only
   * it returns all flaggings matching the given parameters.
   *
   * @code
   *   $flag = \Drupal::service('flag')->getFlagById('bookmark');
   *   $node = Node::load($node_id);
   *   $flaggings = \Drupal::service('flag')->getEntityFlaggings($flag, $node);
   *
   *   foreach ($flaggings as $flagging) {
   *     // Do something with each flagging.
   *   }
   * @endcode
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account of the flagging user. If NULL, flaggings for any
   *   user will be returned.
   * @param string $session_id
   *   (optional) The session ID. This must be supplied if $account is the
   *   anonymous user.
   *
   * @return array
   *   An array of flaggings.
   *
   * @throws \LogicException
   *   An exception is thrown if the given $account is anonymous, but no
   *   $session_id is given.
   */
  public function getEntityFlaggings(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL);

  /**
   * Get all flaggings for the given entity, and optionally, user.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account of the flagging user. If NULL, flaggings for any
   *   user will be returned.
   * @param string $session_id
   *   (optional) The session ID. This must be supplied if $account is the
   *   anonymous user.
   *
   * @return array
   *   An array of flaggings.
   *
   * @throws \LogicException
   *   An exception is thrown if the given $account is anonymous, but no
   *   $session_id is given.
   */
  public function getAllEntityFlaggings(EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL);

  /**
   * Load the flag entity given the ID.
   *
   * @code
   *   $flag = \Drupal::service('flag')->getFlagById('bookmark');
   * @endcode
   *
   * @param string $flag_id
   *   The identifier of the flag to load.
   *
   * @return \Drupal\flag\FlagInterface|null
   *   The flag entity.
   */
  public function getFlagById($flag_id);

  /**
   * Loads the flaggable entity given the flag entity and entity ID.
   *
   * @code
   *   $flag = \Drupal::service('flag')->getFlagById('bookmark');
   *   $flaggable = \Drupal::service('flag')->getFlaggableById($flag, $entity_id);
   * @endcode
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param int $entity_id
   *   The ID of the flaggable entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The flaggable entity object.
   */
  public function getFlaggableById(FlagInterface $flag, $entity_id);

  /**
   * Get a list of users that have flagged an entity.
   *
   * @code
   *   $flag = \Drupal::service('flag')->getFlagById('bookmark');
   *   $node = Node::load($node_id);
   *   $flagging_users = \Drupal::service('flag')->getFlaggingUsers($node, $flag);
   *
   *   foreach ($flagging_users as $user) {
   *     // Do something.
   *   }
   * @endcode
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param \Drupal\flag\FlagInterface $flag
   *   (optional) The flag entity to which to restrict results.
   *
   * @return array
   *   An array of users who have flagged the entity.
   */
  public function getFlaggingUsers(EntityInterface $entity, ?FlagInterface $flag = NULL);

  /**
   * Flags the given entity given the flag and entity objects.
   *
   * To programmatically create a flagging between a flag and an article:
   *
   * @code
   *   $flag_service = \Drupal::service('flag');
   *   $flag = $flag_service->getFlagById('bookmark');
   *   $node = Node::load($node_id);
   *   $flag_service->flag($flag, $node);
   * @endcode
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to flag.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account of the user flagging the entity. If not given,
   *   the current user is used.
   * @param string $session_id
   *   (optional) The session ID. If $account is NULL and the current user is
   *   anonymous, then this can also be omitted to use the current session.
   *   to identify an anonymous user.
   *
   * @return \Drupal\flag\FlagInterface|null
   *   The flagging.
   *
   * @throws \LogicException
   *   An exception is thrown if the given flag, entity, and account are not
   *   compatible in some way:
   *   - The flag applies to a different entity type from the given entity.
   *   - The flag does not apply to the entity's bundle.
   *   - The entity is already flagged with this flag by the user.
   *   - The user is anonymous but not uniquely identified by session_id.
   */
  public function flag(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL);

  /**
   * Unflags the given entity for the given flag.
   *
   * @code
   *   $flag_service = \Drupal::service('flag');
   *   $flag = $flag_service->getFlagById('bookmark');
   *   $node = Node::load($node_id);
   *   $flag_service->unflag($flag, $node);
   * @endcode
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag being unflagged.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to unflag.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The account of the user that created the flagging. Defaults
   *   to the current user.
   * @param string $session_id
   *   (optional) If $account is anonymous then the $session_id MUST be
   *   used to identify a user uniquely. If $account and $session_id are NULL
   *   and the current user is anonymous then the current session_id will be
   *   used.
   *
   * @throws \LogicException
   *   An exception is thrown if the given flag, entity, and account are not
   *   compatible in some way:
   *   - The flag applies to a different entity type from the given entity.
   *   - The flag does not apply to the entity's bundle.
   *   - The entity is not currently flagged with this flag by the user.
   *   - The user is anonymous but not uniquely identified by session_id.
   */
  public function unflag(FlagInterface $flag, EntityInterface $entity, ?AccountInterface $account = NULL, $session_id = NULL);

  /**
   * Remove all flaggings from a flag.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag object.
   */
  public function unflagAllByFlag(FlagInterface $flag);

  /**
   * Remove all flaggings from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function unflagAllByEntity(EntityInterface $entity);

  /**
   * Remove all of a user's flaggings.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user object.
   * @param string $session_id
   *   (optional) The session ID. This must be specified if $account is the
   *   anonymous user.
   *
   * @throws \LogicException
   *   Thrown when $account is anonymous but no associated session ID is
   *   specified.
   */
  public function unflagAllByUser(AccountInterface $account, $session_id = NULL);

  /**
   * Shared helper for user account cancellation or deletion.
   *
   * Removes:
   *   All flags by the user.
   *   All flaggings of the user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user being cancelled or deleted.
   */
  public function userFlagRemoval(UserInterface $account);

  /**
   * Set up values for the flagger user account and session.
   *
   * This is a helper method for functions that allow the flagger account to be
   * omitted to mean the current user.
   *
   * If you always want the current user, you can use this as follows:
   *
   * @code
   *   $account = $session_id = NULL;
   *   \Drupal::service('flag')->populateFlaggerDefaults($account, $session_id);
   * @endcode
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account, or a variable set to NULL (rather than the constant NULL)
   *   to get the current user assigned to it.
   * @param string $session_id
   *   A session ID, or a variable set to NULL to get the session ID assigned to
   *   it in the case that the user also unspecified and anonymous. If $account
   *   is not NULL and is the anonymous user, then this must be specified, and
   *   in this case, it is the caller's responsibility to ensure that the
   *   session is properly started.
   *
   * @throws \LogicException
   *   Throws an exception is $account is specified and is the anonymous user,
   *   but $session_id is NULL.
   */
  public function populateFlaggerDefaults(?AccountInterface &$account = NULL, &$session_id = NULL);

}
