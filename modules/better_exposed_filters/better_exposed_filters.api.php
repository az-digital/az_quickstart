<?php

/**
 * @file
 * Hooks provided by the Better Exposed Filters module.
 */

/**
 * Alters BEF options before the exposed form widgets are built.
 *
 * @param array $options
 *   The BEF options array.
 * @param \Drupal\views\ViewExecutable $view
 *   The view to which the settings apply.
 * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $displayHandler
 *   The display handler to which the settings apply.
 */
function hook_better_exposed_filters_options_alter(array &$options, ViewExecutable $view, DisplayPluginBase $displayHandler) {
  // Set the min/max value of a slider.
  $settings['field_price_value']['slider_options']['bef_slider_min'] = 500;
  $settings['field_price_value']['slider_options']['bef_slider_max'] = 5000;
}
