<?php

/**
 * @file
 * Contains az_author.page.inc.
 *
 * Page callback for Author entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Author templates.
 *
 * Default template: az_author.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_az_author(array &$variables) {
  // Fetch AZAuthor Entity Object.
  $az_author = $variables['elements']['#az_author'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
