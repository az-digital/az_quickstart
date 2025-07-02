<?php

/**
 * @file
 * Theme hooks, preprocessor, and suggestions.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;

/**
 * Implements hook_theme().
 */
function better_exposed_filters_theme($existing, $type, $theme, $path): array {
  return [
    'bef_checkboxes' => [
      'render element' => 'element',
    ],
    'bef_radios' => [
      'render element' => 'element',
    ],
    'bef_links' => [
      'render element' => 'element',
    ],
    'bef_hidden' => [
      'render element' => 'element',
    ],
    'bef_number' => [
      'render element' => 'element',
    ],
  ];
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function better_exposed_filters_theme_suggestions_alter(array &$suggestions, array $variables, $hook): void {
  // Target bef elements.
  if ($hook === 'form_element' || str_starts_with($hook, 'bef_')) {
    $plugin_type = $variables['element']['#context']['#plugin_type'] ?? FALSE;
    if ($plugin_type === 'bef') {
      $view_id = $variables['element']['#context']['#view_id'];
      $display_id = $variables['element']['#context']['#display_id'];
      $field_name = $variables['element']['#name'];

      if ($view_id) {
        $suggestions[] = $hook . '__' . $view_id;
        $suggestions[] = $hook . '__' . $view_id . '__' . $field_name;
        if ($display_id) {
          $suggestions[] = $hook . '__' . $view_id . '__' . $display_id;
          $suggestions[] = $hook . '__' . $view_id . '__' . $display_id . '__' . $field_name;
        }
      }
    }
  }
}

/**
 * Prepares variables for views exposed form templates.
 *
 * Default template: views-exposed-form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 */
function better_exposed_filters_preprocess_views_exposed_form(array &$variables): void {
  // Checks if Token module is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('token')) {
    return;
  }
  // Replaces tokens in description field of the exposed filter.
  foreach ($variables['form']['#info'] as $name => &$info) {
    if (isset($info['description']) && str_starts_with($name, 'filter-') && isset($variables['form'][explode('filter-', $name)[1]]['#description'])) {
      $info['description'] = \Drupal::service('token')->replace($info['description']);
      $variables['form'][explode('filter-', $name)[1]]['#description'] = $info['description'];
    }
  }
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
function template_preprocess_bef_checkboxes(array &$variables): void {
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
    _bef_preprocess_nested_elements($variables);
  }
}

/**
 * Prepares variables for bef-radios template.
 *
 * Default template: bef-radios.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function template_preprocess_bef_radios(array &$variables): void {
  $element = &$variables['element'];

  // Create new wrapper attributes since the element attributes will be used
  // on the fieldset (@see template_preprocess_fieldset).
  $variables['wrapper_attributes'] = new Attribute();

  $variables['children'] = Element::children($element);
  $variables['display_inline'] = $element['#bef_display_inline'] ?? FALSE;

  // Set element name.
  $variables['attributes']['name'] = $element['#name'];

  // Handle nested radio buttons.
  if (!empty($variables['element']['#bef_nested'])) {
    _bef_preprocess_nested_elements($variables);
  }
}

/**
 * Prepares variables for bef-number template.
 *
 * Default template: bef-number.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function template_preprocess_bef_number(array &$variables): void {
  $element = &$variables['element'];
  $variables['children'] = Element::children($element);
  $variables['display_inline'] = $element['#bef_display_inline'] ?? FALSE;

  if (!isset($element['#name'])) {
    $variables['attributes']['name'] = $element['#parent'][0];
  }
  else {
    $variables['attributes']['name'] = $element['#name'];
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
function template_preprocess_bef_hidden(array &$variables): void {
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

  // @todo Check for optgroups.
  // Put subelements in the $element_set array and add a
  // group heading. Otherwise, just add the element to the set.
  // $element_set = array();
  // if (is_array($elem)) {
  // $element_set = $elem;
  // }
  // else {
  // $element_set[$option] = $elem;
  // }
}

/**
 * Prepares variables for bef-links template.
 *
 * Default template: bef-links.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the exposed form element.
 */
function template_preprocess_bef_links(array &$variables): void {
  // Collect some variables before we start tweaking the element.
  $element = &$variables['element'];
  $options = $element['#options'];
  $name = $element['#name'];
  $multiple = $element['#multiple'] ?? FALSE;

  // Set multiple attribute.
  if ($multiple) {
    $variables['attributes']['multiple'] = 'multiple';
  }

  // Set element name.
  $variables['attributes']['name'] = $name;

  // Get the query string arguments from the current request.
  $existing_query = \Drupal::service('request_stack')->getCurrentRequest()->query->all();

  // Remove page parameter from query.
  unset($existing_query['page']);

  // Store selected values.
  $selectedValues = $element['#value'];
  if (!is_array($selectedValues)) {
    $selectedValues = [$selectedValues => $selectedValues];
  }
  $variables['selected'] = $selectedValues;

  // Set hidden elements array.
  $hiddens = [];
  foreach ($options as $k => $v) {
    if (!empty($selectedValues[$k])) {
      $hidden_name = $multiple ? $name . '[' . $k . ']' : $name;
      $hiddens[$hidden_name] = $selectedValues[$k];
    }
  }
  $variables['hiddens'] = $hiddens;

  $variables['links'] = [];
  foreach ($options as $optionValue => $optionLabel) {
    // Build a new Url object for each link since the query string changes with
    // each option.
    /** @var Drupal\Core\Url $url */
    $url = clone($element['#bef_path']);

    // Allow visitors to toggle a filter setting on and off. This is not as
    // simple as setOptions('foo', '') as that still leaves an entry which is
    // rendered rather than removing the entry from the query string altogether.
    // Calling $url->setOption() still leaves a value behind. Instead, we work
    // with the entire options array and remove items from it as needed.
    $urlOptions = $url->getOptions();

    if ($multiple) {
      $newQuery = $existing_query;
      if (!isset($newQuery[$name])) {
        $newQuery[$name] = [];
      }
      if (in_array($optionValue, $selectedValues)) {
        // Allow users to toggle an option using the same link.
        $newQuery[$name] = array_filter($newQuery[$name], function ($value) use ($optionValue) {
          return $value != $optionValue;
        });
      }
      else {
        $newQuery[$name][] = $optionValue;
      }
      if (empty($newQuery[$name])) {
        unset($newQuery[$name]);
      }

      $urlOptions['query'] = $newQuery;
    }
    else {
      if (strval($optionValue) === $element['#value']) {
        // Allow toggle link functionality -- click the same link to turn an
        // option on or off.
        $newQuery = $existing_query;
        unset($newQuery[$name]);
        if (empty($newQuery)) {
          // Remove the query string completely.
          unset($urlOptions['query']);
        }
        else {
          $urlOptions['query'] = $newQuery;
        }
      }
      else {
        $urlOptions['query'] = $existing_query;
        $urlOptions['query'][$name] = $optionValue;
      }
    }

    // Add our updated options to the Url object.
    $url->setOptions($urlOptions);

    // Provide the Twig template with an array of links.
    $variables['links'][$optionValue] = [
      '#attributes' => [
        'id' => Html::getUniqueId('edit-' . implode('-', [$name, $optionValue])),
        'name' => $name . '[' . $optionValue . ']',
        'class' => [
          'bef-link',
        ],
      ],
      '#type' => 'link',
      '#title' => $optionLabel,
      '#url' => $url,
    ];

    /*
     * @see https://stackoverflow.com/questions/13846769/php-in-array-0-value
     */
    if (in_array(strval($optionValue), $selectedValues)) {
      $variables['links'][$optionValue]['#attributes']['class'][] = 'bef-link--selected';
    }
  }

  // Handle nested links. But first add the links as children to the element
  // for consistent processing between checkboxes/radio buttons and links.
  $variables['element'] = array_replace($variables['element'], $variables['links']);
  $variables['children'] = Element::children($variables['element']);
  if (!empty($variables['element']['#bef_nested'])) {
    _bef_preprocess_nested_elements($variables);
  }
}

/******************************************************************************
 * Utility functions for BEF themed elements.
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
function _bef_preprocess_nested_elements(array &$variables): void {
  // Provide a hierarchical info on the element children for the template to
  // render as a nested <ul>. Views prepends '-' characters for each level of
  // depth in the vocabulary. Store that information, but remove the hyphens as
  // we don't want to display them.
  $variables['is_nested'] = TRUE;
  $variables['depth'] = [];
  foreach ($variables['children'] as $child) {
    if ($child === 'All') {
      // For non-required filters, put any/all option at the root.
      $variables['depth'][$child] = 0;
      // And don't change the text as it defaults to "- Any -" and we do not
      // want to remove the leading hyphens.
      continue;
    }

    $original = $variables['element'][$child]['#title'];
    $variables['element'][$child]['#title'] = ltrim($original, '-');
    $variables['depth'][$child] = strlen($original) - strlen($variables['element'][$child]['#title']);
  }
}
