<?php

/**
 * @file
 * Hooks for the devel module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter devel dumper information declared by other modules.
 *
 * @param array $info
 *   Devel dumper information to alter.
 */
function hook_devel_dumper_info_alter(array &$info) {
  $info['default']['label'] = 'Altered label';
}

/**
 * @} End of "addtogroup hooks".
 */
