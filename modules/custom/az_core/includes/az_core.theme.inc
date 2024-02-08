<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggestions.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_theme().
 */
function az_core_theme($existing, $type, $theme, $path) {
  return [
    'az_bef_checkboxes' => [
      'render element' => 'element',
    ],
  ];
}

/******************************************************************************
 * Preprocess functions for BEF themed elements.
 */

/**
 * Prepares variables for bef-checkboxes template.
 *
 * Default template: bef-checkboxes.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function template_preprocess_az_bef_checkboxes(array &$variables) {
  $element = &$variables['element'];

  // Create new wrapper attributes since the element attributes will be used
  // on the fieldset (@see template_preprocess_fieldset).
  $variables['wrapper_attributes'] = new Attribute();

  $variables['children'] = Element::children($element);
  $variables['show_select_all_none'] = $element['#bef_select_all_none'] ?? FALSE;
  $variables['show_select_all_none_nested'] = $element['#bef_select_all_none_nested'] ?? FALSE;
  $variables['display_inline'] = $element['#bef_display_inline'] ?? FALSE;

  // Set element name.
  $variables['attributes']['name'] = $element['#name'];

  // Handle nested checkboxes.
  if (!empty($variables['element']['#bef_nested'])) {
    _az_bef_preprocess_nested_elements($variables);
  }
}

/**
 * Prepares variables for bef-hidden template.
 *
 * Default template: bef-hidden.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function template_preprocess_az_bef_hidden(array &$variables) {
  $element = $variables['element'];

  // This theme function is only used for multi-select elements.
  $variables['is_multiple'] = TRUE;
  $variables['selected'] = empty($element['#value']) ? $element['#default_value'] : $element['#value'];
  $variables['hidden_elements'] = [];
  foreach ($element['#options'] as $value => $label) {
    $variables['hidden_elements'][$value] = [
      '#type' => 'hidden',
      '#value' => $value,
      '#name' => $element['#name'] . '[]',
    ];
  }

}

/******************************************************************************
 * Utility functions for AZ BEF themed elements.
 */

/**
 * Internal function to handled nested form elements.
 *
 * Adds 'is_nested' and 'depth' $variables. Requires 'children' to be set in
 * variables array before being called.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function _az_bef_preprocess_nested_elements(array &$variables) {
  // Provide a hierarchical info on the element children for the template to
  // render as a nested <ul>. Views prepends '-' characters for each level of
  // depth in the vocabulary. Store that information, but remove the hyphens as
  // we don't want to display them.
  $variables['is_nested'] = TRUE;
  $variables['depth'] = [];

  // Initialize an empty array for structured elements.
  $structured_elements = [];

  // Example logic to structure elements (simplified for illustration).
  foreach ($variables['children'] as $child) {
    // Determine if the child has sub-elements (actual children).
    // Calculate depth based on hyphens in the title as a proxy for hierarchy.
    $original = $variables['element'][$child]['#title'];
    $cleanedTitle = ltrim($original, '-');

    $depth = strlen($original) - strlen($cleanedTitle);

    // Update the title and depth information.
    $variables['element'][$child]['#title'] = $cleanedTitle;
    $collapseId = NULL;
    $variables['depth'][$child] = $depth;
    $term_id = $variables['element'][$child]['#return_value'];

    // Return value is a holdover from the form API. It is the value of the
    // element that is returned when the form is submitted. It is used to
    // determine if the element is selected.
    // Its value is the term ID.
    $term_parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($term_id);
    $term_parent_id = reset(array_keys($term_parents));
    $child_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($term_id);

    if ($child === 'All') {
      // Special handling for "All" option.
      $variables['depth'][$child] = 0;
      continue;
    }
    $list_title = [
      '#type' => 'html_tag',
      '#tag' => 'a',
      '#attributes' => [
        'class' => [],
      ],
    ];
    if (!empty($child_terms)) {
      $collapseId = 'collapse-az-bef-' . $term_id;
      $list_title['#value'] = $cleanedTitle;
      $list_title['#attributes']['data-toggle'] = 'collapse';
      $list_title['#attributes']['href'] = '#' . $collapseId;
      $list_title['#attributes']['role'] = 'button';
      $list_title['#attributes']['aria-expanded'] = 'true';
      $list_title['#attributes']['aria-controls'] = $collapseId;
      $list_title['#attributes']['data-collapse-id'] = $collapseId;
      $list_title['#attributes']['class'][] = 'collapser';
      $list_title['#attributes']['class'][] = 'level-' . $depth;
      $list_title['#attributes']['class'][] = 'text-decoration-none';
      if ($depth === 0) {
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'font-weight-bolder';
        $list_title['#attributes']['class'][] = 'text-size-h5';
      }
      else {
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'collapser';
      }
      // Apply the modified list title to the element.
      $variables['element'][$child] = $list_title;
      if ($depth > 0 && !empty($child_terms)) {
        $list_title['#value'] = $cleanedTitle;
        // Apply the modified list title to the element.
        $variables['element'][$child] = $list_title;
      }

    }

  }

  // Assign structured elements for Twig rendering.
  $variables['nested_elements'] = $structured_elements;
}
