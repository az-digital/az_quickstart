<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * JQuery UI date picker widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "az_exposed_filters_datepicker",
 *   label = @Translation("jQuery UI Date Picker"),
 * )
 */
class DatePickers extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    if ((is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) && !$filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $field_id = $this->getExposedFilterFieldId();

    // Handle wrapper element added to exposed filters
    // in https://www.drupal.org/project/drupal/issues/2625136.
    $wrapper_id = $field_id . '_wrapper';
    if (!isset($form[$field_id]) && isset($form[$wrapper_id])) {
      $element = &$form[$wrapper_id][$field_id];
    }
    else {
      $element = &$form[$field_id];
    }

    parent::exposedFormAlter($form, $form_state);

    // Attach the JS (@see /js/datepickers.js)
    $form['#attached']['library'][] = 'az_exposed_filters/datepickers';

    // Date picker settings.
    $element['#attached']['drupalSettings']['az_exposed_filters']['datepicker'] = TRUE;
    $element['#attached']['drupalSettings']['az_exposed_filters']['datepicker_options'] = [];
    $drupal_settings = &$element['#attached']['drupalSettings']['az_exposed_filters']['datepicker_options'];

    // Single Date API-based input element.
    $is_single_date = isset($element['value']['#type'])
      && 'date_text' == $element['value']['#type'];
    // Double Date-API-based input elements such as "in-between".
    $is_double_date = isset($element['min']) && isset($element['max'])
      && 'date_text' == $element['min']['#type']
      && 'date_text' == $element['max']['#type'];

    if ($is_single_date || $is_double_date) {
      if (isset($element['value'])) {
        $format = $element['value']['#date_format'];
        $element['value']['#attributes']['class'][] = 'az-exposed-filters-datepicker';
        $element['value']['#attributes']['autocomplete'] = 'off';
      }
      else {
        // Both min and max share the same format.
        $format = $element['min']['#date_format'];
        $element['min']['#attributes']['class'][] = 'az-exposed-filters-datepicker';
        $element['max']['#attributes']['class'][] = 'az-exposed-filters-datepicker';
        $element['min']['#attributes']['autocomplete'] = 'off';
        $element['max']['#attributes']['autocomplete'] = 'off';
      }

      // Convert Date API format to jQuery UI date format.
      $mapping = $this->getjQueryUiDateFormatting();
      $drupal_settings['dateformat'] = str_replace(array_keys($mapping), array_values($mapping), $format);
    }
    else {
      /*
       * Standard Drupal date field.  Depending on the settings, the field
       * can be at $element (single field) or
       * $element[subfield] for two-value date fields or filters
       * with exposed operators.
       */
      $fields = ['min', 'max', 'value'];
      if (count(array_intersect($fields, array_keys($element)))) {
        foreach ($fields as $field) {
          if (isset($element[$field])) {
            $element[$field]['#attributes']['class'][] = 'az-exposed-filters-datepicker';
            $element[$field]['#attributes']['autocomplete'] = 'off';
          }
        }
      }
      else {
        $element['#attributes']['class'][] = 'az-exposed-filters-datepicker';
        $element['#attributes']['autocomplete'] = 'off';
      }
    }
  }

  /**
   * Convert Date API formatting to jQuery formatDate formatting.
   *
   * @todo: To be honest, I'm not sure this is needed.  Can you set a
   * Date API field to accept anything other than Y-m-d? Well, better
   * safe than sorry...
   *
   * @see http://us3.php.net/manual/en/function.date.php
   * @see http://docs.jquery.com/UI/Datepicker/formatDate
   *
   * @return array
   *   PHP date format => jQuery formatDate format
   *   (comments are for the PHP format, lines that are commented out do
   *   not have a jQuery formatDate equivalent, but maybe someday they
   *   will...)
   */
  private function getjQueryUiDateFormatting() {
    return [
      /* Day */
      // Day of the month, 2 digits with leading zeros 01 to 31.
      'd' => 'dd',
      // A textual representation of a day, three letters  Mon through
      // Sun.
      'D' => 'D',
      // Day of the month without leading zeros  1 to 31.
      'j' => 'd',
      // (lowercase 'L') A full textual representation of the day of the
      // week Sunday through Saturday.
      'l' => 'DD',
      // ISO-8601 numeric representation of the day of the week (added
      // in PHP 5.1.0) 1 (for Monday) through 7 (for Sunday).
      // 'N' => ' ',
      // English ordinal suffix for the day of the month, 2 characters
      // st, nd, rd or th. Works well with j.
      // 'S' => ' ',
      // Numeric representation of the day of the week 0 (for Sunday)
      // through 6 (for Saturday).
      // 'w' => ' ',
      // The day of the year (starting from 0) 0 through 365.
      'z' => 'o',
      /* Week */
      // ISO-8601 week number of year, weeks starting on Monday (added
      // in PHP 4.1.0) Example: 42 (the 42nd week in the year).
      // 'W' => ' ',.
      /* Month */
      // A full textual representation of a month, such as January or
      // March  January through December.
      'F' => 'MM',
      // Numeric representation of a month, with leading zeros 01
      // through 12.
      'm' => 'mm',
      // A short textual representation of a month, three letters  Jan
      // through Dec.
      'M' => 'M',
      // Numeric representation of a month, without leading zeros  1
      // through 12.
      'n' => 'm',
      // Number of days in the given month 28 through 31.
      // 't' => ' ',.
      /* Year */
      // Whether it's a leap year  1 if it is a leap year, 0 otherwise.
      // 'L' => ' ',
      // ISO-8601 year number. This has the same value as Y, except that
      // if the ISO week number (W) belongs to the previous or next
      // year, that year is used instead. (added in PHP 5.1.0).
      // Examples: 1999 or 2003.
      // 'o' => ' ',
      // A full numeric representation of a year, 4 digits Examples:
      // 1999 or 2003.
      'Y' => 'yy',
      // A two digit representation of a year  Examples: 99 or 03.
      'y' => 'y',

      /* Time */
      // Lowercase Ante meridiem and Post meridiem am or pm.
      // 'a' => ' ',
      // Uppercase Ante meridiem and Post meridiem AM or PM.
      // 'A' => ' ',
      // Swatch Internet time  000 through 999.
      // 'B' => ' ',
      // 12-hour format of an hour without leading zeros 1 through 12.
      // 'g' => ' ',
      // 24-hour format of an hour without leading zeros 0 through 23.
      // 'G' => ' ',
      // 12-hour format of an hour with leading zeros  01 through 12.
      // 'h' => ' ',
      // 24-hour format of an hour with leading zeros  00 through 23.
      // 'H' => ' ',
      // Minutes with leading zeros  00 to 59.
      // 'i' => ' ',
      // Seconds, with leading zeros 00 through 59.
      // 's' => ' ',
      // Microseconds (added in PHP 5.2.2) Example: 654321.
      // 'u' => ' ',.
    ];
  }

}
