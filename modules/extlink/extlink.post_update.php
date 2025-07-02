<?php

/**
 * @file
 * Post update hooks for extlink module.
 */

/**
 * Forces a cache rebuild in order to add new `extlink.setting_save.subscriber`.
 */
function extlink_post_update_add_event_subscriber(&$sandbox) {
  // Presence of this hook forces cache rebuild on database update.
}
