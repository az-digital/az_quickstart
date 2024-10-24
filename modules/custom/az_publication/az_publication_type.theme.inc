<?php

/**
 * @file
 * Preprocessors and theme functions for the Views UI.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for AZ Publication type listing templates.
 *
 * Default template: az-publication-type-listing-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - headers: An associative array containing the headers for the type
 *     listing table.
 *   - rows: An associative array containing the rows data for the type
 *     listing table.
 */
function template_preprocess_az_publication_type_listing_table(&$variables) {
  // Convert the attributes to valid attribute objects for headers.
  foreach ($variables['headers'] as $key => $header) {
    // Check if '#attributes' exists and is an array before trying to access it.
    if (is_array($header) && isset($header['#attributes']) && is_array($header['#attributes'])) {
      $variables['headers'][$key]['attributes'] = new Attribute($header['#attributes']);
    }
  }
  // Convert the attributes to valid attribute objects for rows.
  if (!empty($variables['rows'])) {
    foreach ($variables['rows'] as $key => $row) {
      // Check if '#attributes' exists and is an array before trying to
      // access it.
      if (is_array($row) && isset($row['#attributes']) && is_array($row['#attributes'])) {
        $variables['rows'][$key]['attributes'] = new Attribute($row['#attributes']);
      }
    }
  }
}
