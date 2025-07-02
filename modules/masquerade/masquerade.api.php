<?php

/**
 * @file
 * Hooks provided by the Masquerade module.
 */

use Drupal\user\UserInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Control access to masquerade as a certain target user.
 *
 * Modules may implement this hook to control whether a user is allowed to
 * masquerade as a certain target user account.
 *
 * @param \Drupal\user\UserInterface $user
 *   The currently logged-in user.
 * @param \Drupal\user\UserInterface $target_account
 *   The target user account to check for masquerade access.
 *
 * @return bool|null
 *   Either a Boolean or NULL:
 *   - FALSE to explicitly deny access. If a module denies access, no other
 *     module is able to grant access and access is denied.
 *   - TRUE to grant access. Access is only granted if at least one module
 *     grants access and no module denies access.
 *   - NULL or nothing to not affect the operation. If no module explicitly
 *     grants access, access is denied.
 */
function hook_masquerade_access(UserInterface $user, UserInterface $target_account) {
  // Explicitly deny access for uid 1.
  if ($target_account->id() == 1) {
    return FALSE;
  }
  // Example: If the target username is 'demo', always grant access for everone.
  if ($target_account->label() == 'demo') {
    return TRUE;
  }
  // In other cases do not alter access.
}

/**
 * @} End of "addtogroup hooks".
 */
