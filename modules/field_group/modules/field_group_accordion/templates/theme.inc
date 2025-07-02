<?php

/**
 * @file
 * Preprocessors for fieldgroup accordion elements.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for fieldgroup accordion templates.
 *
 * Default template: field-group-accordion.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the accordion element. Properties used: #children.
 */
function template_preprocess_field_group_accordion(array &$variables) {
  $element = $variables['element'];
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';
}

/**
 * Prepares variables for fieldgroup accordion item templates.
 *
 * Default template: field-group-accordion-item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and children of
 *     the accordion item element.
 */
function template_preprocess_field_group_accordion_item(array &$variables) {

  $element = $variables['element'];

  if (!empty($element['#title'])) {
    $variables['title'] = $element['#title'];
  }

  if (!empty($element['#description'])) {
    $variables['description'] = $element['#description'];
  }

  $variables['open'] = $element['#open'];
  $variables['label_attributes'] = new Attribute();
  $variables['children'] = (!empty($element['#children'])) ? $element['#children'] : '';

}
