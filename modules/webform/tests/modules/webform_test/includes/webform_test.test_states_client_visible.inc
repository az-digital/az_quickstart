<?php

/**
 * @file
 * Generate test elements with #states.
 */

/**
 * Generate test elements with #states.
 *
 * @return array
 *   An array containing test elements with #states.
 */
function webform_test_test_states_client_visible() {
  \Drupal::moduleHandler()->loadInclude('webform_test', 'inc', 'includes/webform_test.test_states');

  $elements = [];
  $elements['trigger'] = [
    '#type' => 'checkbox',
    '#title' => 'Displays and require elements',
  ];
  $elements += _webform_test_states('visible', [
    '#states' => [
      'visible' => [
        ':input[name="trigger"]' => [
          'checked' => TRUE,
        ],
      ],
      'required' => [
        ':input[name="trigger"]' => [
          'checked' => TRUE,
        ],
      ],
    ],
  ]);
  return $elements;
}
