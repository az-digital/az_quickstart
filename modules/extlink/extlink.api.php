<?php

/**
 * @file
 * Hooks related to the extlink module.
 */

/**
 * Allow other modules to alter the Extlink settings.
 *
 * @param array $settings
 *   Array of all ExtLink settings.
 */
function hook_extlink_settings_alter(array &$settings) {
  // Add one CSS selector to ignore links that match that.
  $settings['extlink_css_exclude'] .= ', .my-module a.button';
}

/**
 * Allow other modules to alter the excluded CSS selector settings.
 *
 * @param string $cssExclude
 *   Comma separated CSS selectors for links that should be ignored.
 */
function hook_extlink_css_exclude_alter(&$cssExclude) {
  // Add one CSS selector to ignore links that match that.
  $cssExclude .= ', .my-module a.button';
}
