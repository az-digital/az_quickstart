<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggestions.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Markup;

/**
 * Implements hook_theme().
 */
function az_core_theme($existing, $type, $theme, $path) {
  return [
    'az_finder_widget' => [
      'render element' => 'element',
     ],
  ];
}

/******************************************************************************
 * Preprocess functions for BEF themed elements.
 */

/**
 * Prepares variables for az-finder template.
 *
 * Default template: az-finder.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
// function template_preprocess_az_finder(array &$variables) {

// }
function template_preprocess_az_finder_widget(array &$variables) {
  $element = &$variables['element'];
  $variables['wrapper_attributes'] = new Attribute();
  $variables['children'] = Element::children($element);
  $variables['attributes']['name'] = $element['#name'];
  if (!empty($element['#hierarchy'])) {
    _az_bef_preprocess_nested_elements($variables);
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
  $element = $variables['element'];
  // Default azurite color
  $default_color = '#1E5288';
  $default_title_expand = t('Expand this section');
  $default_title_collapse = t('Collapse this section');

  $level_0_expand_icon_fill_color = $element['#level_0_expand_color'] ?? $default_color;
  $level_0_collapse_icon_fill_color = $element['#level_0_collapse_color'] ?? $default_color;
  $level_1_expand_icon_fill_color = $element['#level_1_expand_color'] ?? $default_color;
  $level_1_collapse_icon_fill_color = $element['#level_1_collapse_color'] ?? $default_color;

  $level_0_expand_icon_title = $element['#level_0_expand_title'] ?? $default_title_expand;
  $level_0_collapse_icon_title = $element['#level_0_collapse_title'] ?? $default_title_collapse;
  $level_1_expand_icon_title = $element['#level_1_expand_title'] ?? $default_title_expand;
  $level_1_collapse_icon_title = $element['#level_1_collapse_title'] ?? $default_title_collapse;

  // Example logic to structure elements (simplified for illustration).
  foreach ($variables['children'] as $child) {

    if ($child === 'All') {
      // Special handling for "All" option.
      $variables['depth'][$child] = 0;
      continue;
    }
    $renderer = \Drupal::service('renderer');
    $taxonomy_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $term_id = $element[$child]['#return_value'];
    $child_terms = $taxonomy_storage->loadChildren($term_id);
    $original_title = $element[$child]['#title'];
    $cleaned_title = ltrim($original_title, '-');
    $list_title = [
      '#type' => 'html_tag',
    ];
    // Determine if the child has sub-elements (actual children).
    // Calculate depth based on hyphens in the title as a proxy for hierarchy.
    $depth = strlen($original_title) - strlen($cleaned_title);
    $level_0_expand_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6-1.41-1.41z"/></svg>',
      '#context' => [
        'title' => $level_0_expand_icon_title,
        'fill_color' => $level_0_expand_icon_fill_color,
      ],
    ];
    $level_0_collapse_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="m12 8-6 6 1.41 1.41L12 10.83l4.59 4.58L18 14l-6-6z"/></svg>',
      '#context' => [
        'title' => $level_0_collapse_icon_title,
        'fill_color' => $level_0_collapse_icon_fill_color,
      ],
    ];
    $level_1_expand_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
      '#context' => [
        'title' => $level_1_expand_icon_title,
        'fill_color' => $level_1_expand_icon_fill_color,
      ],
    ];
    $level_1_collapse_icon = [
      '#type' => 'inline_template',
      '#template' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" title="{{ title }}"><path fill="{{ fill_color }}" d="M19 13H5v-2h14v2z"/></svg>',
      '#context' => [
        'title' => $level_1_collapse_icon_title,
        'fill_color' => $level_1_collapse_icon_fill_color,
      ],
    ];
    $list_title['#value'] = $cleaned_title;
    // // Decide which icon to use based on depth.
    $collapse_icon = $depth === 0 ? $level_0_collapse_icon : $level_1_collapse_icon;
    $variables['depth'][$child] = $depth;
    if (!empty($child_terms) && $depth === 1) {
      $icon_html = $renderer->render($collapse_icon);
      $list_title['#value'] =  Markup::create($icon_html . ' ' . $cleaned_title);
    } else {
      $list_title['#value'] = $cleaned_title;
      $variables['element'][$child]['#title'] = $list_title['#value'];
    }

    if (!empty($child_terms) && $depth <= 1) {

      $list_title_link = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'class' => [],
        ],
      ];

      $collapse_id = 'collapse-az-finder-' . $term_id;
      $list_title_link['#attributes']['data-toggle'] = 'collapse';
      $list_title_link['#attributes']['href'] = '#' . $collapse_id;
      $list_title_link['#attributes']['class'][] = 'd-block';
      $list_title_link['#attributes']['role'] = 'button';
      $list_title_link['#attributes']['aria-expanded'] = 'true';
      $list_title_link['#attributes']['aria-controls'] = $collapse_id;
      $list_title_link['#attributes']['data-collapse-id'] = $collapse_id;
      $list_title_link['#attributes']['class'][] = 'collapser';
      $list_title_link['#attributes']['class'][] = 'level-' . $depth;
      $list_title_link['#attributes']['class'][] = 'text-decoration-none';
      if ($depth === 0) {
        $list_title['icon'] = $collapse_icon;
        $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-0';
        $list_title['#tag'] = 'h3';
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'text-size-h5';
        $list_title['#attributes']['class'][] = 'mt-3';
        $list_title['#attributes']['class'][] = 'pt-3';
      }
      else {
        $list_title_link['#attributes']['class'][] = 'js-svg-replace-level-1';
        $list_title['#tag'] = 'h4';
        $list_title['#attributes']['class'][] = 'text-azurite';
        $list_title['#attributes']['class'][] = 'text-size-h6';
      }

      $list_title_link['value'] = $list_title;
      $list_title_link['#attached']['drupalSettings']['azFinder']['icons'] = [
        'level_0_expand' => $renderer->render($level_0_expand_icon),
        'level_0_collapse' => $renderer->render($level_0_collapse_icon),
        'level_1_expand' => $renderer->render($level_1_expand_icon),
        'level_1_collapse' => $renderer->render($level_1_collapse_icon),
      ];

      // Apply the modified list title to the element.
      $variables['element'][$child] = $list_title_link;
    }
  }
}
