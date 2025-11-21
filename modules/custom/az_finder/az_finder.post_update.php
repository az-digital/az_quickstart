<?php

/**
 * @file
 * Post update functions for AZ Finder.
 */

use Drupal\Core\Config\FileStorage;

/**
 * Migrate active_filter_indicator_on_top_level_terms to active_filter_indicator_levels.
 */
function az_finder_post_update_migrate_filter_indicator_setting(&$sandbox) {
  $config_factory = \Drupal::configFactory();

  // Update all view configs that use az_better_exposed_filters.
  $view_storage = \Drupal::entityTypeManager()->getStorage('view');
  $view_ids = $view_storage->getQuery()->execute();

  foreach ($view_ids as $view_id) {
    $view = $view_storage->load($view_id);
    $displays = $view->get('display');
    $changed = FALSE;

    foreach ($displays as $display_id => &$display) {
      // Check if this display uses az_better_exposed_filters.
      if (isset($display['display_options']['exposed_form']['type']) &&
          $display['display_options']['exposed_form']['type'] === 'az_better_exposed_filters') {

        $options = &$display['display_options']['exposed_form']['options'];

        // Check if the old boolean setting exists.
        if (isset($options['active_filter_indicator_on_top_level_terms'])) {
          $old_value = $options['active_filter_indicator_on_top_level_terms'];

          // Convert boolean to integer:
          // TRUE -> 1 (show on level 0 only)
          // FALSE -> NULL (disabled)
          if ($old_value === TRUE) {
            $options['active_filter_indicator_levels'] = 1;
          }
          else {
            $options['active_filter_indicator_levels'] = NULL;
          }

          // Remove the old setting.
          unset($options['active_filter_indicator_on_top_level_terms']);
          $changed = TRUE;
        }
      }
    }

    if ($changed) {
      $view->set('display', $displays);
      $view->save();
      \Drupal::logger('az_finder')->notice('Migrated active filter indicator setting for view: @view_id', [
        '@view_id' => $view_id,
      ]);
    }
  }

  return t('Migrated active_filter_indicator_on_top_level_terms to active_filter_indicator_levels for all views.');
}
