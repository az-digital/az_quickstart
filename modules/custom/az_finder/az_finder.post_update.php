<?php

/**
 * @file
 * Post update functions for AZ Finder.
 */

/**
 * Add active_filter_indicator_on_top_level_terms where az_finder_tid_widget is used.
 */
function az_finder_post_update_add_filter_indicator_to_tid_widgets(&$sandbox = NULL): void {
  $storage = \Drupal::entityTypeManager()->getStorage('view');
  $views = $storage->loadMultiple();

  foreach ($views as $view) {
    $view_changed = FALSE;

    foreach ($view->get('display') as $display_id => $display) {
      $filters = $display['display_options']['filters'] ?? [];
      $uses_az_finder_tid_widget = FALSE;

      foreach ($filters as $filter_config) {
        if (
          !empty($filter_config['expose']['widget']) &&
          $filter_config['expose']['widget'] === 'az_finder_tid_widget'
        ) {
          $uses_az_finder_tid_widget = TRUE;
          break;
        }
      }

      if (
        $uses_az_finder_tid_widget &&
        !isset($display['display_options']['exposed_form']['options']['active_filter_indicator_on_top_level_terms'])
      ) {
        $view->set("display.$display_id.display_options.exposed_form.options.active_filter_indicator_on_top_level_terms", FALSE);
        $view_changed = TRUE;
      }
    }

    if ($view_changed) {
      $view->save();
    }
  }
}
