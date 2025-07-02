<?php

/**
 * @file
 * Generate long webform card elements.
 */

use Drupal\webform\WebformInterface;

/**
 * Generate long webform card elements.
 *
 * @param \Drupal\webform\WebformInterface $webform
 *   A webform object.
 *
 * @return array
 *   An array containing long webform elements.
 */
function webform_test_test_cards_long(WebformInterface $webform) {
  $length = (int) str_replace('test_cards_long_', '', $webform->id());
  $elements = [];
  for ($i = 1; $i <= $length; $i++) {
    $elements["card_$i"] = [
      '#type' => 'webform_card',
      '#title' => (string) t('Card #@index', ['@index' => $i]),
    ];
    $elements["card_$i"]["element_$i"] = [
      '#type' => 'textfield',
      '#title' => (string) t('Element #@index', ['@index' => $i]),
    ];
  }
  return $elements;
}
