<?php

/**
 * @file
 * Options alter hooks.
 */

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Language\LanguageManager;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for range options.
 *
 * @see config/install/webform.webform.example_options.yml
 */
function webform_webform_options_range_alter(array &$options, array $element = []) {
  $element += [
    '#min' => 1,
    '#max' => 100,
    '#step' => 1,
    '#pad_length' => NULL,
    '#pad_str' => 0,
  ];

  $options = WebformOptionsHelper::range(
    $element['#min'],
    $element['#max'],
    $element['#step'],
    $element['#pad_length'],
    $element['#pad_str']
  );
}

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for time zones options.
 */
function webform_webform_options_time_zones_alter(array &$options, array $element = []) {
  if (empty($options)) {
    $options = TimeZoneFormHelper::getOptionsList();
  }
}

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for country codes options.
 */
function webform_webform_options_country_codes_alter(array &$options, array $element = []) {
  if (empty($options)) {
    /** @var \Drupal\Core\Locale\CountryManagerInterface $country_manager */
    $country_manager = \Drupal::service('country_manager');
    $options = $country_manager->getList();
  }
}

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for country names options.
 */
function webform_webform_options_country_names_alter(array &$options, array $element = []) {
  if (empty($options)) {
    /** @var \Drupal\Core\Locale\CountryManagerInterface $country_manager */
    $country_manager = \Drupal::service('country_manager');
    $countries = $country_manager->getList();
    $options = array_combine($countries, $countries);
  }
}

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for languages options.
 */
function webform_webform_options_languages_alter(array &$options, array $element = []) {
  if (empty($options)) {
    $languages = LanguageManager::getStandardLanguageList();
    unset($languages['en-x-simple']);
    $options = [];
    foreach ($languages as $language) {
      $options[$language[0]] = $language[0];
    }
  }
}

/**
 * Implements hook_webform_options_WEBFORM_OPTIONS_ID_alter() for translations options.
 */
function webform_webform_options_translations_alter(array &$options, array $element = []) {
  if (empty($options)) {
    $languages = \Drupal::languageManager()->getLanguages();
    $options = [];
    foreach ($languages as $language) {
      $options[$language->getId()] = $language->getName();
    }
  }
}
