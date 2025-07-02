<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Date picker widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "bef_datepicker",
 *   label = @Translation("Date Picker"),
 * )
 */
class DatePickers extends FilterWidgetBase {

  use LoggerChannelTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $filter = NULL, array $filter_options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    if ((is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) && !$filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * In case of date offsets as a default value, convert to dates.
   *
   * @param array $element
   *   The form element to process.
   */
  protected function convertOffsets(array &$element): void {
    $options = $this->handler->options;

    if ($options['value']['type'] !== 'offset') {
      return;
    }
    unset($options['value']['type']);

    foreach (array_keys($options['value']) as $key) {
      if (!array_key_exists($key, $element) || !array_key_exists('#default_value', $element[$key])) {
        continue;
      }

      // Convert offset initial values to dates.
      if ($element[$key]['#default_value'] === $options['value'][$key]) {
        try {
          $date = new \DateTime($element[$key]['#default_value']);
          $element[$key]['#default_value'] = $date->format('Y-m-d');
        }
        catch (\Exception $e) {
          $this->getLogger('better_exposed_filters')->log(RfcLogLevel::ERROR, $e->getMessage());
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
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
    $form['#attached']['library'][] = 'better_exposed_filters/datepickers';

    // Date picker settings.
    $element['#attached']['drupalSettings']['better_exposed_filters']['datepicker'] = TRUE;
    $element['#attached']['drupalSettings']['better_exposed_filters']['datepicker_options'] = [];
    $drupal_settings = &$element['#attached']['drupalSettings']['better_exposed_filters']['datepicker_options'];

    // Single Date API-based input element.
    $is_single_date = isset($element['value']['#type'])
      && 'date_text' == $element['value']['#type'];
    // Double Date-API-based input elements such as "in-between".
    $is_double_date = isset($element['min']['#type']) && isset($element['max']['#type'])
      && 'date_text' === $element['min']['#type'] && 'date_text' === $element['max']['#type'];

    if ($is_single_date || $is_double_date) {
      if (isset($element['value'])) {
        $format = $element['value']['#date_format'];
        $element['value']['#type'] = 'date';
        $element['value']['#attributes']['class'][] = 'bef-datepicker';
        $element['value']['#attributes']['autocomplete'] = 'off';
      }
      else {
        // Both min and max share the same format.
        $format = $element['min']['#date_format'];
        $element['min']['#type'] = 'date';
        $element['max']['#type'] = 'date';
        $element['min']['#attributes']['class'][] = 'bef-datepicker';
        $element['max']['#attributes']['class'][] = 'bef-datepicker';
        $element['min']['#attributes']['autocomplete'] = 'off';
        $element['max']['#attributes']['autocomplete'] = 'off';
      }

      $drupal_settings['dateformat'] = $format;
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
            $element[$field]['#type'] = 'date';
            $element[$field]['#attributes']['class'][] = 'bef-datepicker';
            $element[$field]['#attributes']['autocomplete'] = 'off';
          }
        }
      }
      else {
        $element['#type'] = 'date';
        $element['#attributes']['class'][] = 'bef-datepicker';
        $element['#attributes']['autocomplete'] = 'off';
      }
    }

    $this->convertOffsets($element);
  }

}
