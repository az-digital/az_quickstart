<?php

/**
 * @file
 * Hooks defined by Upgrade Status.
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Extension\Extension;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the operations run on projects on the Upgrade Status UI.
 *
 * @param array $operations
 *   Batch operations array to be altered.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The submitted state of the upgrade status form.
 */
function hook_upgrade_status_operations_alter(array &$operations, FormStateInterface $form_state) {
  // Duplicate each operation with another one that runs rector on the
  // same extension.
  if (!empty($form_state->getValue('run_rector'))) {
    $keys = array_keys($operations);
    foreach ($keys as $key) {
      $operations[] = [
        'update_rector_run_rector_batch',
        [$operations[$key][1][0]],
      ];
    }
  }
}

/**
 * Alter the build array for an upgrade status result group.
 *
 * @param array $build
 *   A render array with build results, including a 'title', 'description',
 *   'errors', etc. keys.
 * @param \Drupal\Core\Extension\Extension $extension
 *   Drupal extension object.
 * @param string $group_key
 *   The key for the result group. One of 'rector', 'now', 'uncategorized',
 *   'later' or 'ignore'.
 */
function hook_upgrade_status_result_alter(array &$build, Extension $extension, $group_key) {
  if ($group_key == 'rector') {
    $build['description']['#markup'] = t('Here is your patch...');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
