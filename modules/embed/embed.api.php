<?php

/**
 * @file
 * Hooks provided by the Embed module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the embed type plugin definitions.
 *
 * @param array &$plugins
 *   An associative array containing the embed type plugin definitions keyed by
 *   plugin ID.
 *
 * @see \Drupal\embed\EmbedType\EmbedTypeManager
 */
function hook_embed_type_plugins_alter(array &$plugins) {
  if (isset($plugins['entity'])) {
    $plugins['entity']['label'] = 'A better label for embedded entities';
  }
}

/**
 * @} End of "addtogroup hooks".
 */
