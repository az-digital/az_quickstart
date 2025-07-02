<?php

namespace Drupal\flag;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the interface for Flag entities.
 *
 * You can create a Flag either through the Admin UI or programmatically. For
 * example, to create a flag for 'article' nodes using the 'reload' link type:
 *
 * @code
 *    $flag = Flag::create([
 *       'id' => 'New flag',
 *       'entity_type' => 'node',
 *       'bundles' => [
 *         'article',
 *       ],
 *       'flag_type' => 'entity:node',
 *       'link_type' => 'reload',
 *       'flagTypeConfig' => [],
 *       'linkTypeConfig' => [],
 *   ]);
 *   $flag->save();
 * @endcode
 */
interface FlagInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /* @todo: Add getters and setters as necessary. */

  /**
   * Returns true of there's a flagging for this flag and the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The flaggable entity.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   (optional) The account of the user that flagged the entity.
   * @param string|null $session_id
   *   (optional) Session id.
   *
   * @return bool
   *   True if the given entity is flagged, FALSE otherwise.
   *
   * @throws \LogicException
   *   Thrown when $account is anonymous but no associated session ID is
   *   specified.
   */
  public function isFlagged(EntityInterface $entity, ?AccountInterface $account = NULL, ?string $session_id = NULL);

  /**
   * Returns the flaggable entity type ID.
   *
   * @return string
   *   The flaggable entity ID.
   */
  public function getFlaggableEntityTypeId();

  /**
   * Get the flag bundles property.
   *
   * @return array
   *   An array containing the bundles this flag may be applied to. An empty
   *   array indicates all bundles are valid.
   *
   * @see getApplicableBundles()
   */
  public function getBundles();

  /**
   * Get the bundles this flag may be applied to.
   *
   * For the verbatim value of the flag's types property, use getBundles().
   *
   * @return array
   *   An array containing the bundles this flag may be applied to.
   */
  public function getApplicableBundles();

  /**
   * Get the flag type plugin.
   *
   * @return \Drupal\flag\FlagType\FlagTypePluginInterface
   *   The flag type plugin for the flag.
   */
  public function getFlagTypePlugin();

  /**
   * Set the flag type plugin.
   *
   * @param string $plugin_id
   *   A string containing the flag type plugin ID.
   */
  public function setFlagTypePlugin($plugin_id);

  /**
   * Get the link type plugin for this flag.
   *
   * @return \Drupal\flag\ActionLink\ActionLinkTypePluginInterface
   *   The link type plugin for the flag.
   */
  public function getLinkTypePlugin();

  /**
   * Set the link type plugin.
   *
   * @param string $plugin_id
   *   A string containing the link type plugin ID.
   */
  public function setlinkTypePlugin($plugin_id);

  /**
   * Returns an associative array of permissions used by flag_permission().
   *
   * Typically there are two permissions, one to flag, and one to unflag.
   * Each key of the array is the permission name. Each value is an array with
   * a single element, 'title', which provides the display name for the
   * permission.
   *
   * @return array
   *   An array of permissions.
   *
   * @see \Drupal\flag\Entity\Flag::actionPermissions()
   */
  public function actionPermissions();

  /**
   * Returns true if the flag is global, false otherwise.
   *
   * Global flags disable the default behavior of a Flag. Instead of each
   * user being able to flag or unflag the entity, a global flag may be flagged
   * once for all users. The flagging's uid base field is set to the account
   * that performed the flagging action in all cases.
   *
   * @return bool
   *   TRUE if the flag is global, FALSE otherwise.
   */
  public function isGlobal();

  /**
   * Sets the flag as global or not.
   *
   * @param bool $global
   *   TRUE to mark the flag as global, FALSE for the default behavior.
   *
   * @see \Drupal\flag\Entity\Flag::isGlobal()
   */
  public function setGlobal($global);

  /**
   * The flag short text.
   *
   * @param string $text
   *   The flag short text to set.
   */
  public function setFlagShortText($text);

  /**
   * Gets the flag short text.
   *
   * @param string $action
   *   The flag action, either 'flag' or 'unflag'.
   *
   * @return string
   *   A string containing the flag short text.
   */
  public function getShortText($action);

  /**
   * Gets the flag long text.
   *
   * @param string $action
   *   The flag action, either 'flag' or 'unflag'.
   *
   * @return string
   *   A string containing the flag long text.
   */
  public function getLongText($action);

  /**
   * Sets the flag long text.
   *
   * @param string $flag_long
   *   The flag long text to use.
   */
  public function setFlagLongText($flag_long);

  /**
   * Gets the flag message.
   *
   * @param string $action
   *   The flag action, either 'flag' or 'unflag'.
   *
   * @return string
   *   The unflag message text to use.
   */
  public function getMessage($action);

  /**
   * Sets the flag message.
   *
   * @param string $flag_message
   *   The flag message text to use.
   */
  public function setFlagMessage($flag_message);

  /**
   * Sets the unflag short text.
   *
   * @param string $flag_short
   *   The unflag short text to use.
   */
  public function setUnflagShortText($flag_short);

  /**
   * Sets the unflag long text.
   *
   * @param string $unflag_long
   *   The unflag long text to use.
   */
  public function setUnflagLongText($unflag_long);

  /**
   * Sets the unflag message.
   *
   * @param string $unflag_message
   *   The unflag message text to use.
   */
  public function setUnflagMessage($unflag_message);

  /**
   * Get the flag's weight.
   *
   * @return int
   *   The flag's weight.
   */
  public function getWeight();

  /**
   * Set the flag's weight.
   *
   * @param int $weight
   *   An int containing the flag weight to use.
   */
  public function setWeight($weight);

  /**
   * Get the flag's unflag denied message text.
   *
   * @return string
   *   A string containing the unflag denied message text.
   */
  public function getUnflagDeniedText();

  /**
   * Set's the flag's unflag denied message text.
   *
   * @param string $unflag_denied_text
   *   The unflag denied message text to use.
   */
  public function setUnflagDeniedText($unflag_denied_text);

  /**
   * Checks whether a user has permission to flag/unflag or not.
   *
   * @param string $action
   *   The action for which to check permissions, either 'flag' or 'unflag'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) An AccountInterface object.
   * @param \Drupal\Core\Entity\EntityInterface $flaggable
   *   (optional) The flaggable entity.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   An AccessResult object.
   */
  public function actionAccess($action, ?AccountInterface $account = NULL, ?EntityInterface $flaggable = NULL);

}
