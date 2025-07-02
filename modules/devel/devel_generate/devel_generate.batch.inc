<?php

/**
 * @file
 * Provides common batch functions for every DevelGeneratePlugin.
 */

use Drupal\devel_generate\DevelGenerateBaseInterface;

/**
 * Calls the correct method responsible for handling a given batch operation.
 */
function devel_generate_operation(DevelGenerateBaseInterface $class, $method, $vars, &$context) {
  return $class->$method($vars, $context);
}

/**
 * Standard finish batch function.
 */
function devel_generate_batch_finished($success, $results, $operations) {

  if ($success) {
    if (!empty($results['num_translations'])) {
      $message = t('Finished @num elements and @num_translations translations created successfully.', ['@num' => $results['num'], '@num_translations' => $results['num_translations']]);
    }
    else {
      $message = t('Finished @num elements created successfully.', ['@num' => $results['num']]);
    }
  }
  else {
    $message = t('Finished with an error.');
  }
  \Drupal::messenger()->addMessage($message);
}
