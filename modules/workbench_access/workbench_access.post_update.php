<?php

/**
 * @file
 * Contains post update hooks.
 */

/**
 * Implements hook_removed_post_updates().
 */
function workbench_access_removed_post_updates(): array {
  return [
    'workbench_access_post_update_apply_context_mapping_to_blocks' => '2.0.0',
    'workbench_access_post_update_workbench_access_field_delete' => '2.0.0',
    'workbench_access_post_update_section_user_association' => '2.0.0',
    'workbench_access_post_update_section_role_association' => '2.0.0',
    'workbench_access_post_update_convert_to_scheme' => '2.0.0',
    'workbench_access_post_update_convert_role_storage_keys' => '2.0.0',
    'workbench_access_post_update_convert_user_storage_keys' => '2.0.0',
  ];
}
