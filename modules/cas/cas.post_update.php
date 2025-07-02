<?php

/**
 * @file
 * Post-update functions for CAS module.
 */

/**
 * Add prevent normal login and restrict password management error messages.
 */
function cas_post_update_8001() {
  \Drupal::configFactory()->getEditable('cas.settings')
    ->set('error_handling.message_prevent_normal_login', 'This account must log in using <a href="[cas:login-url]">CAS</a>.')
    ->set('error_handling.message_restrict_password_management', 'The requested account is associated with CAS and its password cannot be managed from this website.')
    ->save();
}

/**
 * Set default value for new gateway method config option.
 */
function cas_post_update_8002() {
  $casConfig = \Drupal::configFactory()->getEditable('cas.settings');

  // We need to migrate away from the single config var we had for indicating
  // how CAS gateway operated into the new config settings.
  $oldGatewaySetting = $casConfig->get('gateway.check_frequency');
  // This value was used to indicate CAS gateway was enabled and should check
  // every page request. We use a -1 recheck time to indicate that.
  if ($oldGatewaySetting === 0) {
    $enabled = TRUE;
    $recheckTime = -1;
  }
  // This value was used to indicate CAS gateway was enabled and should check
  // once per user session. We don't have that once per session feature anymore,
  // so default to 12 hrs.
  elseif ($oldGatewaySetting === -1) {
    $enabled = TRUE;
    $recheckTime = 720;
  }
  // All other settings mean it was disabled. We still set a default value for
  // recheck time, but we make sure it remains disabled.
  else {
    $enabled = FALSE;
    $recheckTime = 720;
  }

  $casConfig
    ->set('gateway.method', 'server_side')
    ->set('gateway.recheck_time', $recheckTime)
    ->set('gateway.enabled', $enabled)
    ->clear('gateway.check_frequency')
    ->save();
}

/**
 * Set the auto_register_follow_registration_policy setting.
 */
function cas_post_update_8003(): void {
  \Drupal::configFactory()->getEditable('cas.settings')
    ->set('user_accounts.auto_register_follow_registration_policy', FALSE)
    ->save();
}
