<?php

/**
 * @file
 * Hooks provided by the Flag module.
 */

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter flag type definitions provided by other modules.
 *
 * This hook may be placed in a $module.flag.inc file.
 *
 * @param array $definitions
 *   An array of flag definitions returned by hook_flag_type_info().
 *
 * @See \Drupal\flag\FlagType\FlagTypePluginManager
 */
function hook_flag_type_info_alter(array &$definitions) {

}

/**
 * Alter a flag's default options.
 *
 * Modules that wish to extend flags and provide additional options must declare
 * them here so that their additions to the flag admin form are saved into the
 * flag object.
 *
 * @param array $options
 *   The array of default options for the flag type, with the options for the
 *   flag's link type merged in.
 * @param \Drupal\flag\FlagInterface $flag
 *   The flag object.
 */
function hook_flag_options_alter(array &$options, FlagInterface $flag) {

}

/**
 * Alter other modules' definitions of flag link types.
 *
 * This hook may be placed in a $module.flag.inc file.
 *
 * @param array $link_types
 *   An array of the link types defined by all modules.
 *
 * @see \Drupal\flag\ActionLink\ActionLinkPluginManager
 */
function hook_flag_link_type_info_alter(array &$link_types) {

}

/**
 * Perform access checks when acting on a flag.
 *
 * @param string $action
 *   The action about to be carried out. Either 'flag' or 'unflag'.
 * @param \Drupal\flag\FlagInterface $flag
 *   The flag object.
 * @param \Drupal\Core\Session\AccountInterface $account
 *   The user account performing the action.
 * @param \Drupal\Core\Entity\EntityInterface $flaggable
 *   The flaggable entity.
 *
 * @return \Drupal\Core\Access\AccessResult
 *   An AccessResult object.
 */
function hook_flag_action_access($action, FlagInterface $flag, AccountInterface $account, ?EntityInterface $flaggable = NULL) {
  return AccessResult::neutral();
}
