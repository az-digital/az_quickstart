<?php

/**
 * @file
 * Preprocessors and theme functions for the Views UI.
 */

use Drupal\Core\Template\Attribute;

/**
 * Prepares variables for Views UI view listing templates.
 *
 * Default template: views-ui-view-listing-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - headers: An associative array containing the headers for the view
 *     listing table.
 *   - rows: An associative array containing the rows data for the view
 *     listing table.
 */
function template_preprocess_az_publication_type_listing_table(&$variables) {
  // Convert the attributes to valid attribute objects.

  foreach ($variables['headers'] as $key => $header) {
    // dpm($variables['headers'][$header]);

    // dpm($variables);
    if (isset($variables['headers'][$key])) {
      dpm($header);
      // if (isset($variables['headers'][$key]['attributes'])) {
      //   dpm($variables['headers'][$key]);
      // }

    }
    // $variables['headers'][$key]['attributes'] = new Attribute($header['#attributes']);
  }

  if (!empty($variables['rows'])) {
    foreach ($variables['rows'] as $key => $row) {
      $variables['rows'][$key]['attributes'] = new Attribute($row['#attributes']);
    }
  }
}

