<?php

/**
 * @file
 * Post update functions for Flag.
 */

use Drupal\system\Entity\Action;

/**
 * Implements hook_post_update_NAME().
 *
 * Updates the dependency information in views that depend on flag.
 */
function flag_post_update_flag_relationship_dependencies(&$sandbox) {
  // Load all views.
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();

  /** @var \Drupal\views\Entity\View[] $views */
  foreach ($views as $view) {
    // Views that use the flag_relationship plugin will depend on the Flag
    // module already.
    if (in_array('flag', $view->getDependencies()['module'], TRUE)) {
      $old_dependencies = $view->getDependencies();
      // If we've changed the dependencies, for example, to add a dependency on
      // the flag used in the relationship, then re-save the view.
      if ($old_dependencies !== $view->calculateDependencies()->getDependencies()) {
        $view->save();
      }
    }
  }
}

/**
 * Implements hook_post_update_NAME().
 *
 * Update the flag and unflag actions for existing flags.
 */
function flag_post_update_flag_actions() {
  $flags = \Drupal::entityTypeManager()->getStorage('flag')->loadMultiple();
  $action_names = [];
  foreach ($flags as $flag) {
    $action_names[] = 'flag_action.' . $flag->id() . '.flag';
    $action_names[] = 'flag_action.' . $flag->id() . '.unflag';
  }
  $actions = Action::loadMultiple($action_names);
  foreach ($actions as $old_id => $action) {
    if (preg_match('/\.(un)?flag$/', $old_id)) {
      // Update the plugin ID and the action ID.
      $new_id = preg_replace('/\.((un)?flag)$/', '_\\1', $old_id);
      $new_plugin_id = preg_replace('/^flag_action\./', 'flag_action:', $new_id);
      $action->setPlugin($new_plugin_id);
      $action->set('id', $new_id);
      $action->save();
    }
  }
}

/**
 * Implements hook_post_update_NAME().
 *
 * Rebuild container for updated twig service.
 */
function flag_post_update_flag_count_twig() {
  // No-operation.
}
