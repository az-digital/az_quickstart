<?php

/**
 * @file
 * Preprocessors for fieldgroup elements.
 */

/**
 * Prepares variables for horizontal tabs templates.
 *
 * Default template: horizontal-tabs.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the details element. Properties used: #children.
 */
function template_preprocess_horizontal_tabs(array &$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup html element templates.
 *
 * Default template: field-group-html-element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the html element.
 */
function template_preprocess_field_group_html_element(array &$variables) {

  $element = $variables['element'];

  if (!empty($element['#title']) && !empty($element['#title_element'])) {
    $variables['title_element'] = $element['#title_element'];
    $variables['title'] = $element['#title'];
    $variables['title_attributes'] = $element['#title_attributes'];
  }

  $variables['collapsible'] = (!empty($element['#effect']) && $element['#effect'] !== 'none');
  $variables['wrapper_element'] = $element['#wrapper_element'];
  $variables['attributes'] = $element['#attributes'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}
