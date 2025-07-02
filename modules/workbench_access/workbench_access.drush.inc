<?php

/**
 * @file
 * Drush commands for Workbench Access.
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Implements hook_drush_command().
 */
function workbench_access_drush_command() {
  $items = [];

  $items['workbench-access-test'] = [
    'description' => 'Install test vocabulary for Workbench Access.',
    'aliases' => ['wa-test'],
  ];
  $items['workbench-access-flush'] = [
    'description' => 'Flush section assignments for users and roles.',
    'aliases' => ['wa-flush'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function workbench_access_drush_help($section) {
  $items = workbench_access_drush_command();
  $name = str_replace('workbench access:', '', $section);
  if (isset($items[$name])) {
    return dt($items[$name]['description']);
  }
}

/**
 * Installs the test vocabulary.
 *
 * @todo Refactor
 */
function drush_workbench_access_test() {
  try {
    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Workbench Access',
      'description' => 'Test taxonomy for Workbench Access',
      'vid' => 'workbench_access',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      'weight' => 100,
    ]);
    $vocabulary->save();
    // Create some terms.
    $terms = [
      'Alumni',
      'Faculty',
      'Staff',
      'Students',
    ];
    $children = [
      'Directory',
      'Information',
    ];

    foreach ($terms as $name) {
      $term = Term::create([
        'name' => $name,
        'description' => [],
        'vid' => $vocabulary->id(),
        'parent' => 0,
        'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
      ]);
      $term->save();
      foreach ($children as $child) {
        $child = Term::create([
          'name' => "$name $child",
          'description' => [],
          'vid' => $vocabulary->id(),
          'parent' => $term->id(),
          'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        ]);
        $child->save();
      }
    }
  }
  catch (Exception $e) {
    echo('The test vocabulary has already been created.');
  }
}

/**
 * Flushes assigned user permissions.
 */
function drush_workbench_access_flush() {
  $section_storage = \Drupal::entityTypeManager()->getStorage('section_association');
  foreach (\Drupal::entityTypeManager()->getStorage('access_scheme')->loadMultiple() as $scheme) {
    $sections = $section_storage->loadByProperties([
      'access_scheme' => $scheme->id(),
    ]);
    $section_storage->delete($sections);
  }
  echo('User and role assignments cleared.');
}
