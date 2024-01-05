<?php

/**
 * @file
 * Hooks provided by the AZ Exposed Filters module.
 */

/**
 * Alters AZ Exposed Filters options before the exposed form widgets are built.
 *
 * @param array $options
 *   The AZ Exposed Filters options array.
 * @param \Drupal\views\ViewExecutable $view
 *   The view to which the settings apply.
 * @param \Drupal\views\Plugin\views\display\DisplayPluginBase $displayHandler
 *   The display handler to which the settings apply.
 */
function hook_az_exposed_filters_options_alter(array &$options, ViewExecutable $view, DisplayPluginBase $displayHandler) {
  // Set the min/max value of a slider.
  $settings['field_price_value']['slider_options']['az_exposed_filters_slider_min'] = 500;
  $settings['field_price_value']['slider_options']['az_exposed_filters_slider_max'] = 5000;
}

/**
 * Modify the array of AZ Exposed Filters display options for an exposed filter.
 *
 * @param array $widgets
 *   The set of AZ Exposed Filters widgets available to this filter.
 * @param \Drupal\views\Plugin\views\HandlerBase $filter
 *   The exposed views filter plugin.
 */
function hook_az_exposed_filters_filter_widgets_alter(array &$widgets, HandlerBase $filter) {
  if ($filter instanceof CustomViewsFilterFoo) {
    $widgets['az_exposed_filters_links'] = t('Links');
  }
}
