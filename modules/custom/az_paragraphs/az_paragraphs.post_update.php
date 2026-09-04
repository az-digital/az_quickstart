<?php

/**
 * @file
 * Post update functions for az_paragraphs module.
 */

/**
 * Adds the Arizona Bootstrap 5.2.0 list bullet styles to az_standard.
 */
function az_paragraphs_post_update_add_az_bootstrap_5_2_list_styles(&$sandbox = NULL) {
  if (!\Drupal::moduleHandler()->moduleExists('editor')) {
    return;
  }

  $editor = \Drupal::entityTypeManager()->getStorage('editor')->load('az_standard');
  if (empty($editor)) {
    return;
  }

  $settings = $editor->getSettings();
  // Sites that have never enabled the Style plugin have nothing to add to.
  if (!isset($settings['plugins']['ckeditor5_style']['styles'])) {
    return;
  }
  $styles = $settings['plugins']['ckeditor5_style']['styles'];

  $new_styles = [
    'az-list-focus-points' => [
      'label' => 'Focus Point Bullets',
      'element' => '<ul class="az-list-focus-points">',
    ],
    'az-list-checkmarks' => [
      'label' => 'Checkmark Bullets',
      'element' => '<ul class="az-list-checkmarks">',
    ],
  ];

  // Skip any style whose class a site has already added under its own label.
  $existing = implode(' ', array_column($styles, 'element'));
  foreach (array_keys($new_styles) as $class) {
    if (str_contains($existing, $class)) {
      unset($new_styles[$class]);
    }
  }
  if (empty($new_styles)) {
    return;
  }

  // Keep the new bullet styles next to the existing Triangle Bullets entry.
  $offset = count($styles);
  foreach ($styles as $delta => $style) {
    if (str_contains($style['element'] ?? '', 'az-list-triangles')) {
      $offset = $delta + 1;
      break;
    }
  }
  array_splice($styles, $offset, 0, array_values($new_styles));

  $settings['plugins']['ckeditor5_style']['styles'] = $styles;
  $editor->setSettings($settings);
  $editor->save();

  \Drupal::logger('az_quickstart')->notice('Added the following Arizona Bootstrap 5.2.0 list bullet styles to the az_standard text editor: @styles', [
    '@styles' => implode(', ', array_column($new_styles, 'label')),
  ]);
}
