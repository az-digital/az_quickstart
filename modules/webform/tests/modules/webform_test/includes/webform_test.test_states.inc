<?php

/**
 * @file
 * Generate test elements with #states.
 */

use Drupal\webform\Entity\Webform;

/**
 * Generates a render array of example elements.
 *
 * @param string $type
 *   Type of state being generated.
 * @param array $default_properties
 *   Associative array of default element properties.
 *
 * @return array
 *   A render array of example elements
 */
function _webform_test_states($type, array $default_properties = []) {
  $ignored_element_types = [
    'captcha',
    'machine_name',
    'table',
    'webform_card',
    'webform_more',
    'webform_table',
    'webform_table_row',
  ];

  $data = [
    'containers' => [],
    'elements' => [],
  ];

  // Create a webform which will be used to generate test data.
  $webform = Webform::create();

  /** @var \Drupal\webform\WebformSubmissionGenerateInterface $generate */
  $generate = \Drupal::service('webform_submission.generate');

  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');
  $elements = $element_manager->getInstances();
  foreach ($elements as $element_type => $webform_element) {
    if (preg_match('/(webform_options_custom:|webform_options_custom_entity:)/', $element_type)
    || in_array($element_type, $ignored_element_types)) {
      continue;
    }

    $element = _webform_test_get_element_preview($element_type);
    if (!$element) {
      continue;
    }

    // Set default container content.
    if ($webform_element->isContainer($element)) {
      // Containers can't be disabled.
      if ($type === 'disabled') {
        continue;
      }
    }
    else {
      // Set default test data as default value.
      // Don't generate a managed file and signature element.
      if (!preg_match('/^(.*_file|webform_signature)$/', $element_type)) {
        if ($value = $generate->getTestValue($webform, $element_type, $element, ['random' => FALSE])) {
          $element['#default_value'] = $value;
        }
      }
    }

    // Set default properties.
    $element += $default_properties;

    $group = ($webform_element->isContainer($element)) ? 'containers' : 'elements';

    $element_key = str_replace(':', '_', $element_type);
    $data[$group][$element_key] = $element;
  }

  return $data['containers'] + $data['elements'];
}

/* ************************************************************************** */
// Generate #states API issues webform as Markdown for docs/issues.md.
/* ************************************************************************** */

/**
 * Get #states API issues as Markdown.
 *
 * @return string
 *   #states API issues as Markdown.
 */
function webform_test_elements_states_issues_markdown() {
  $issues = _webform_test_issues();

  /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
  $element_manager = \Drupal::service('plugin.manager.webform.element');

  $markdown = [];
  foreach ($issues as $element_type => $element_issues) {
    $element = $element_manager->getElementInstance(['#type' => $element_type]);
    $markdown[] = '#### ' . $element->getPluginLabel() . ' (' . $element->getTypeName() . ')';
    $markdown[] = '';
    foreach ($element_issues as $issue_number => $issue_title) {
      $markdown[] = "**[Issue #$issue_number: $issue_title](https://www.drupal.org/node/$issue_number)**";
      $markdown[] = '';
    }
  }
  return implode(PHP_EOL, $markdown);
}
