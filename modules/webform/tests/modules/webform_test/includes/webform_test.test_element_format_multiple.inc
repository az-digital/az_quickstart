<?php

/**
 * @file
 * Generate test multiple elements with formatting.
 */

use Drupal\webform\WebformInterface;

\Drupal::moduleHandler()->loadInclude('webform_test', 'inc', 'includes/webform_test.test_element_format');

/**
 * Generate test element formats with multiple values.
 *
 * @return array
 *   An array containing test elements formats.
 */
function webform_test_test_element_format_multiple(WebformInterface $webform) {
  return webform_test_test_element_format($webform, FALSE, TRUE);
}
