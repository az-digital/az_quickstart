<?php

namespace Drupal\smart_date_recur;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\smart_date\SmartDateTrait;

/**
 * Provides friendly methods for smart date range.
 */
trait SmartDateRecurTrait {

  use StringTranslationTrait;
  use SmartDateTrait;

  /**
   * Helper function to massage an array for inclusion in output.
   */
  protected function massageForOutput($output, mixed $settings, $add_classes = NULL) {
    $settings = $this->normalizeSettings($settings);
    if ($add_classes === NULL) {
      $add_classes = $settings['add_classes'] ?? FALSE;
    }
    if ($settings['date_first']) {
      // Time should be first so reverse the array.
      ksort($output);
    }
    $temp_array['start'] = $output;
    if ($add_classes) {
      $this->addRangeClasses($temp_array);
    }
    return $temp_array['start'];
  }

  /**
   * Helper function to create a collapsed display of events within a day.
   */
  protected function formatWithinDay(array $instances, mixed $settings) {
    $settings = $this->normalizeSettings($settings);
    $settings_notime = $this->settingsFormatNoTime($settings);
    $settings_nodate = $this->settingsFormatNoDate($settings);
    $settings_notz = $this->settingsFormatNoTz($settings_nodate);
    $output = [];
    foreach ($instances as $time_set) {
      $this_output = [];
      $time_output = [];
      $last_time = array_pop($time_set);
      foreach ($time_set as $key => $instance) {
        $time_output[$key] = $this->formatSmartDate($instance->value, $instance->end_value, $settings_notz, $instance->timezone);
        $time_output[$key]['#suffix'] = ', ';
      }
      $time_output[] = $this->formatSmartDate($last_time->value, $last_time->end_value, $settings_nodate, $last_time->timezone);
      $this_output['time'] = $time_output;
      $this_output['join'] = ['#markup' => $settings['join']];
      $this_output['date']['#markup'] = $this->formatSmartDate($last_time->value, $last_time->value, $settings_notime, $last_time->timezone, 'string');
      $this_output['#attributes']['class'] = ['smart-date--daily-times'];
      $this_output['#type'] = 'container';
      $output[] = $this->massageForOutput($this_output, $settings);
    }
    return $output;
  }

  /**
   * Format the configured number of upcoming and past instances.
   *
   * @param array $instances
   *   The values to draw from.
   * @param int $next_index
   *   The value from which to calculate.
   * @param mixed $settings
   *   The settings used to render the instances.
   * @param bool $within_day
   *   Whether or not to format for recurring within a day.
   *
   * @return array
   *   The formatted render array.
   */
  public function subsetInstances(array $instances, $next_index, mixed $settings = [], $within_day = FALSE) {
    $settings = $this->normalizeSettings($settings);
    $periods = ['past_display', 'upcoming_display'];
    $period_instances = [];

    // Get the specified number of past instances.
    $past_display = $settings['past_display'] ?? 0;

    // Display past instances if set and at least one instances in the past.
    if ($past_display && $next_index) {
      if ($next_index == -1) {
        $begin = count($instances) - $past_display;
      }
      else {
        $begin = $next_index - $past_display;
      }
      if ($begin < 0) {
        $past_display += $begin;
        $begin = 0;
      }
      $period_instances['past_display'] = array_slice($instances, $begin, $past_display, TRUE);
    }

    $upcoming_display = $settings['upcoming_display'] ?? 0;
    // Display upcoming instances if set and at least one instance upcoming.
    if ($upcoming_display && $next_index < count($instances) && $next_index != -1) {
      $period_instances['upcoming_display'] = array_slice($instances, $next_index, $upcoming_display, TRUE);
    }

    $rrule_output = [
      '#theme' => 'smart_date_recurring_formatter',
    ];

    foreach ($periods as $period) {
      if (empty($period_instances[$period])) {
        continue;
      }
      $rrule_output['#' . $period] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
      ];
      if ($within_day) {
        $items = $this->formatWithinDay($period_instances[$period], $settings);
      }
      else {
        $items = [];
        foreach ($period_instances[$period] as $key => $item) {
          // Check for manual key and use, if set.
          $delta = $item->delta ?? $key;
          $items[$delta] = $this->buildOutput($delta, $item, $settings);
        }
      }
      foreach ($items as $delta => $item) {
        $rrule_output['#' . $period]['#items'][$delta] = [
          '#children' => $item,
          '#theme' => 'container',
        ];
      }
    }
    if (!empty($settings['show_next']) && !empty($rrule_output['#upcoming_display']['#items'])) {
      $rrule_output['#next_display'] = array_shift($rrule_output['#upcoming_display']['#items']);
    }
    return $rrule_output;
  }

  /**
   * Helper function to create and augment formatted output.
   *
   * @param int $key
   *   Numeric key of the output delta.
   * @param object $item
   *   Field values.
   * @param mixed $settings
   *   The settings used to render the instances.
   *
   * @return array
   *   Render array of the formatted output.
   */
  protected function buildOutput($key, $item, mixed $settings = []) {
    $settings = $this->normalizeSettings($settings);
    if (!$item || empty($item->value)) {
      return [];
    }
    $output = $this->formatSmartDate($item->value, $item->end_value, $settings, $item->timezone);
    if (!empty($settings['add_classes'])) {
      $this->addRangeClasses($output);
    }
    if (!empty($settings['time_wrapper'])) {
      $this->addTimeWrapper($output, $item->value, $item->end_value, $item->timezone);
    }
    if (!empty($settings['augmenters']['instances']) && method_exists($this, 'augmentOutput')) {
      $this->augmentOutput($output, $settings['augmenters']['instances'], $item->value, $item->end_value, $item->timezone, $key, 'instances');
    }
    return $output;
  }

  /**
   * Helper function to find the next instance from now in a provided range.
   */
  public function findNextInstance(array $instances, mixed $settings = []) {
    $settings = $this->normalizeSettings($settings);
    $next_index = -1;
    $time = $settings['min_date'] ?? time();
    $current_upcoming = $settings['current_upcoming'] ?? TRUE;
    foreach ($instances as $index => $instance) {
      $date_compare = ($current_upcoming) ? $instance->end_value : $instance->value;
      if ($date_compare > $time) {
        $next_index = $index;
        break;
      }
    }
    return $next_index;
  }

  /**
   * Helper function to find the next instance from now in a provided range.
   */
  public function findNextInstanceByDay(array $dates, $today) {
    $next_index = -1;
    foreach ($dates as $index => $date) {
      if ($date >= $today) {
        $next_index = $index;
        break;
      }
    }
    return $next_index;
  }

  /**
   * Retrieve the months_limit value from the field definition.
   */
  public function getThirdPartyFallback($field_def, $property, $default = NULL) {
    $value = $default;
    if (method_exists($field_def, 'getThirdPartySetting')) {
      // Works for field definitions and rule objects.
      $value = $field_def
        ->getThirdPartySetting('smart_date_recur', $property, $default);
    }
    elseif (method_exists($field_def, 'getSetting')) {
      // For custom entities, set value in your field definition.
      $value = $field_def->getSetting($property);
    }
    return $value;
  }

  /**
   * Retrieve the months_limit value from the field definition.
   */
  public function getMonthsLimit($field_def) {
    $month_limit = $this->getThirdPartyFallback($field_def, 'month_limit', 12);
    return $month_limit;
  }

  /**
   * Return an array of frequency labels.
   */
  public function getFrequencyLabels() {
    return [
      'MINUTELY' => $this->t('By Minutes'),
      'HOURLY' => $this->t('Hourly'),
      'DAILY' => $this->t('Daily'),
      'WEEKLY' => $this->t('Weekly'),
      'MONTHLY' => $this->t('Monthly'),
      'YEARLY' => $this->t('Annually'),
    ];
  }

  /**
   * Return an array of frequency labels.
   */
  public function getFrequencyLabelsOrNull() {
    $values = ['none' => 'Not recurring'];
    $labels = $this->getFrequencyLabels();
    return array_merge($values, $labels);
  }

}
