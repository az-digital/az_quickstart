<?php

/**
 * @file
 * Post update functions for AZ Finder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;

/**
 * Add default reset_button_classes to existing views using az_better_exposed_filters.
 */
function az_finder_post_update_add_reset_button_classes(&$sandbox) {
  // Use the same default classes as defined in QuickstartExposedFilters.
  // This should match QuickstartExposedFilters::DEFAULT_RESET_BUTTON_CLASSES.
  $default_classes = 'btn btn-sm btn-secondary w-100 mx-1 mb-3';
  
  \Drupal::classResolver()
    ->getInstanceFromDefinition(ConfigEntityUpdater::class)
    ->update($sandbox, 'view', function (ViewEntityInterface $view) use ($default_classes) {
      $displays = $view->get('display');
      $updated = FALSE;
      
      foreach ($displays as $display_id => $display) {
        // Check if this display uses az_better_exposed_filters.
        if (isset($display['display_options']['exposed_form']['type']) &&
            $display['display_options']['exposed_form']['type'] === 'az_better_exposed_filters') {
          
          // Add reset_button_classes if not already set.
          if (!isset($display['display_options']['exposed_form']['options']['reset_button_classes'])) {
            $display['display_options']['exposed_form']['options']['reset_button_classes'] = $default_classes;
            $displays[$display_id] = $display;
            $updated = TRUE;
          }
        }
      }
      
      if ($updated) {
        $view->set('display', $displays);
        return TRUE;
      }
      
      return FALSE;
    });
}
