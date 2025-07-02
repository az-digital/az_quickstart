<?php

namespace Drupal\date_ap_style;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Services for formatting date types using AP Style Date rules.
 */
class ApStyleDateFormatter {

  use StringTranslationTrait;

  /**
   * Configuration object for AP Style settings.
   */
  protected ImmutableConfig $config;

  /**
   * Constructor.
   */
  public function __construct(
    protected LanguageManagerInterface $languageManager,
    protected ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get('date_ap_style.dateapstylesettings');
  }

  /**
   * Get base default options from config when none are provided.
   *
   * @return array<string,bool>
   *   The default config settings.
   */
  private function getConfigOptions(): array {
    return [
      'always_display_year' => $this->config->get('always_display_year'),
      'display_day' => $this->config->get('display_day'),
      'use_today' => $this->config->get('use_today'),
      'cap_today' => $this->config->get('cap_today'),
      'display_time' => $this->config->get('display_time'),
      'hide_date' => $this->config->get('hide_date'),
      'time_before_date' => $this->config->get('time_before_date'),
      'use_all_day' => $this->config->get('use_all_day'),
      'display_noon_and_midnight' => $this->config->get('display_noon_and_midnight'),
      'capitalize_noon_and_midnight' => $this->config->get('capitalize_noon_and_midnight'),
      'timezone' => $this->config->get('timezone'),
      'month_only' => $this->config->get('month_only'),
      'separator' => $this->config->get('separator'),
    ];
  }

  /**
   * Gets a default options array to set base option keys.
   *
   * @return array<string,bool>
   *   The default options when no config is set.
   */
  private function getDefaultOptions(): array {
    return [
      'always_display_year' => FALSE,
      'display_day' => FALSE,
      'use_today' => FALSE,
      'cap_today' => FALSE,
      'display_time' => FALSE,
      'hide_date' => FALSE,
      'time_before_date' => FALSE,
      'use_all_day' => FALSE,
      'display_noon_and_midnight' => FALSE,
      'capitalize_noon_and_midnight' => FALSE,
      'timezone' => '',
      'month_only' => FALSE,
      'separator' => 'to',
    ];
  }

  /**
   * Gets the merged option set.
   *
   * Merges user-specified options with default key values. If no user options
   * are provided, retrieves the default configuration options.
   */
  private function getOptions(array $options): array {
    if (empty($options)) {
      return $this->getConfigOptions();
    }
    else {
      $defaults = $this->getDefaultOptions();
      return array_merge($defaults, $options);
    }
  }

  /**
   * Format a timestamp to an AP style date format.
   *
   * @param int $timestamp
   *   The timestamp to convert.
   * @param array<string,bool> $options
   *   An array of options that affect how the date string is formatted.
   * @param \DateTimeZone|string|null $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string|null $langcode
   *   The language code.
   * @param string|null $fieldtype
   *   Type of field. Example smartdate.
   *
   * @return string
   *   The formatted date string.
   */
  public function formatTimestamp(int $timestamp, array $options = [], \DateTimeZone|string|null $timezone = NULL, ?string $langcode = NULL, ?string $fieldtype = NULL): string {
    $options = $this->getOptions($options);

    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }

    // If no timezone is specified, use the user's if available, or the site
    // or system default.
    if (empty($timezone)) {
      $timezone = date_default_timezone_get();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $datetime_settings = [
      'langcode' => $langcode,
    ];

    $date_string = '';
    $format_date = '';

    // Create a DrupalDateTime object from the timestamp and timezone.
    $date = DrupalDateTime::createFromTimestamp($timestamp, $timezone, $datetime_settings);
    $now = new DrupalDateTime('now', $timezone, $datetime_settings);
    if ($options['month_only']) {
      $format_date .= $this->formatMonth($date);
    }
    else {
      if ($options['use_today'] && $date->format('Y-m-d') == $now->format('Y-m-d')) {
        $date_string = $this->t('today');
        if ($options['cap_today']) {
          $date_string = ucfirst($date_string);
        }
      }
      // Determine if the date is within the current week and set final output.
      elseif ($options['display_day'] && $date->format('W o') == $now->format('W o')) {
        $format_date .= 'l';
      }
      else {
        $format_date .= $this->formatMonth($date) . ' j';
        $format_date .= $this->formatYear($date, $now, $options['always_display_year']);
      }
    }
    $date_string .= $date->format($format_date);

    if ($options['display_time'] && !$options['month_only']) {

      switch ($date->format('H:i')) {
        case '00:00':
          if ($options['use_all_day']) {
            $ap_time_string = $this->t('All Day');
          }
          else {
            $ap_time_string = $options['display_noon_and_midnight'] ? $this->t('midnight') : $date->format('g a');
            if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
              $ap_time_string = ucfirst($ap_time_string);
            }
          }
          break;

        case '12:00':
          $ap_time_string = $options['display_noon_and_midnight'] ? $this->t('noon') : $date->format('g a');
          if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
            $ap_time_string = ucfirst($ap_time_string);
          }
          break;

        default:
          if ($date->format('i') === '00') {
            // Don't display the minutes if it's the top of the hour.
            $ap_time_string = $date->format('g a');
          }
          else {
            $ap_time_string = $date->format('g:i a');
          }
          break;
      }

      // Format the meridian if it's there.
      $ap_time_string = str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $ap_time_string);

      if ($options['hide_date']) {
        $output = $ap_time_string;
      }
      elseif ($options['time_before_date']) {
        $output = $ap_time_string . ', ' . $date_string;
      }
      else {
        $output = $date_string . ', ' . $ap_time_string;
      }
    }
    else {
      $output = $date_string;
    }

    return $output;
  }

  /**
   * Format a timestamp to an AP style date format.
   *
   * @param string[] $timestamps
   *   The start and end timestamps to convert.
   * @param array<string,bool> $options
   *   An array of options that affect how the date string is formatted.
   * @param \DateTimeZone|string|null $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string|null $langcode
   *   The language code.
   * @param string|null $fieldtype
   *   Type of field. Example smartdate.
   *
   * @return string
   *   The formatted date string.
   */
  public function formatRange(array $timestamps, array $options = [], \DateTimeZone|string|null $timezone = NULL, ?string $langcode = NULL, ?string $fieldtype = NULL): string {
    if (empty($timestamps)) {
      return '';
    }

    $options = $this->getOptions($options);
    $normalized_timestamps = $this->getTimeStamps($timestamps, $timezone, $langcode);

    /** @var \Drupal\Core\Datetime\DrupalDateTime $start_stamp */
    $start_stamp = $normalized_timestamps['start_stamp'];
    /** @var \Drupal\Core\Datetime\DrupalDateTime $end_stamp */
    $end_stamp = $normalized_timestamps['end_stamp'];
    /** @var \Drupal\Core\Datetime\DrupalDateTime $now */
    $now = $normalized_timestamps['now'];

    $format_start_date = $format_end_date = $start_date_string = $end_date_string = '';

    // Check if the start date is today.
    $is_start_today = ($options['use_today'] ?? FALSE) && $start_stamp->format('Y-m-d') == $now->format('Y-m-d');
    // Check if the end date is today.
    $is_end_today = ($options['use_today'] ?? FALSE) && $end_stamp->format('Y-m-d') == $now->format('Y-m-d');
    if ($is_start_today && $is_end_today) {
      $date_output = $this->t('today');
      if ($options['cap_today'] ?? FALSE) {
        $date_output = ucfirst($date_output);
      }
    }
    elseif ($options['month_only']) {
      if ($start_stamp->format('Y-m') == $end_stamp->format('Y-m')) {
        // Same month and year.
        $format_start_date = $this->formatMonth($start_stamp);
        $format_end_date = str_replace(',', '', $this->formatYear($end_stamp, $now, $options['always_display_year']));
      }
      elseif ($start_stamp->format('Y') == $end_stamp->format('Y')) {
        // Different months, same year.
        $format_start_date = $this->formatMonth($start_stamp);
        $format_end_date = $this->formatMonth($end_stamp);
        $format_end_date .= str_replace(',', '', $this->formatYear($end_stamp, $now, $options['always_display_year']));
      }
      else {
        // Different years.
        $format_start_date = $this->formatMonth($start_stamp);
        $format_start_date .= str_replace(',', '', $this->formatYear($start_stamp, $now, $options['always_display_year']));
        $format_end_date = $this->formatMonth($end_stamp);
        $format_end_date .= str_replace(',', '', $this->formatYear($end_stamp, $now, $options['always_display_year']));
      }
    }
    elseif ($start_stamp->format('Y-m-d') == $end_stamp->format('Y-m-d')) {
      // The Y-M-D is identical.
      if ($options['display_day'] && $start_stamp->format('W o') == $now->format('W o')) {
        $format_start_date .= 'l, ';
      }
      $format_start_date .= $this->formatMonth($start_stamp) . ' j';
      // Display Y if not equal to current year or option set to always show
      // year.
      $format_start_date .= $this->formatYear($start_stamp, $now, $options['always_display_year']);
    }
    elseif ($start_stamp->format('Y-m') == $end_stamp->format('Y-m')) {
      // The Y-M is identical, but different D.
      $format_start_date = $this->formatMonth($start_stamp) . ' j';
      $format_end_date = 'j';
      // Display Y if end_time year not equal to current year.
      $format_end_date .= $this->formatYear($end_stamp, $now, $options['always_display_year']);
    }
    elseif ($start_stamp->format('Y') == $end_stamp->format('Y')) {
      // The Y is identical, but different M-D.
      $format_start_date = $this->formatMonth($start_stamp) . ' j';
      $format_end_date = $this->formatMonth($end_stamp) . ' j';
      // Display Y if end_time year not equal to current year.
      $format_end_date .= $this->formatYear($end_stamp, $now, $options['always_display_year']);
    }
    elseif ($start_stamp->format('m-d') == $end_stamp->format('m-d')) {
      // The M-D is identical, but different Y.
      $format_start_date = $this->formatMonth($start_stamp) . ' j, Y';
      $format_end_date = 'Y';
    }
    else {
      // All three are different or only D is identical.
      $format_start_date = $this->formatMonth($start_stamp) . ' j, Y';
      $format_end_date = $this->formatMonth($end_stamp) . ' j, Y';
    }

    $date_output = $date_output ?? $start_stamp->format($format_start_date);
    if (!empty($format_end_date)) {
      $date_output .= ($options['separator'] == 'endash' ? ' &ndash; ' : ' to ') . $end_stamp->format($format_end_date);
    }
    if ($options['display_time']) {
      $time_output = $this->getTimeOutput($normalized_timestamps, $options, $fieldtype);
      if ($options['hide_date']) {
        $output = $time_output;
      }
      elseif (!empty($time_output) && $options['time_before_date']) {
        $output = $time_output . ', ' . $date_output;
      }
      elseif (!empty($time_output)) {
        $output = $date_output . ', ' . $time_output;
      }
      else {
        $output = $date_output;
      }
    }
    else {
      $output = $date_output;
    }
    return $output;

  }

  /**
   * Return month format code based on AP Style rules.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   Drupal date object.
   *
   * @return string
   *   Date format string.
   */
  private function formatMonth(DrupalDateTime $date): string {
    return match ($date->format('m')) {
      // Short months get the full print out of their name.
      '03', '04', '05', '06', '07' => 'F',
      // September is abbreviated to 'Sep' by PHP, but we want 'Sept'.
      '09' => 'M\t.',
      // Other months get an abbreviated print-out followed by a period.
      default => 'M.',
    };
  }

  /**
   * Check if year is needed in format and provide format value.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $time_stamp
   *   The date to check.
   * @param \Drupal\Core\Datetime\DrupalDateTime $now
   *   The current date.
   * @param bool $always_display_year
   *   The option to display year when current year.
   *
   * @return string
   *   The format value.
   */
  protected function formatYear(DrupalDateTime $time_stamp, DrupalDateTime $now, ?bool $always_display_year = FALSE): string {
    $format_year = '';
    if ($always_display_year || $time_stamp->format('Y') != $now->format('Y')) {
      $format_year = ', Y';
    }
    return $format_year;
  }

  /**
   * Create an array of Drupal DateTimes.
   *
   * @param string[] $timestamps
   *   The start and end timestamps to convert.
   * @param \DateTimeZone|string|null $timezone
   *   \DateTimeZone object, time zone string or NULL. NULL uses the
   *   default system time zone. Defaults to NULL.
   * @param string|null $langcode
   *   The language code.
   *
   * @return string[]
   *   Array with Drupal DateTimes.
   *     - start_stamp
   *     - end_stamp
   *     - now
   */
  private function getTimeStamps(array $timestamps = [], \DateTimeZone|string|null $timezone = NULL, string $langcode = NULL): array {

    if (empty($langcode)) {
      $langcode = $this->languageManager->getCurrentLanguage()->getId();
    }
    // Create a DrupalDateTime object from the timestamp and timezone.
    $datetime_settings = [
      'langcode' => $langcode,
    ];
    // If no timezone is specified, use the user's if available, or the site
    // or system default.
    if (empty($timezone)) {
      $timezone = date_default_timezone_get();
    }

    // Create a DrupalDateTime object from the timestamp and timezone.
    $start_stamp = DrupalDateTime::createFromTimestamp($timestamps['start'], $timezone, $datetime_settings);
    $now = new DrupalDateTime('now', $timezone, $datetime_settings);
    if (!empty($timestamps['end']) || $timestamps['end'] != 0) {
      $end_stamp = DrupalDateTime::createFromTimestamp($timestamps['end'], $timezone, $datetime_settings);
    }
    else {
      $end_stamp = $start_stamp;
    }

    return [
      'start_stamp' => $start_stamp,
      'end_stamp' => $end_stamp,
      'now' => $now,
    ];

  }

  /**
   * Format a timestamp to an AP style time format.
   *
   * @param string[] $timestamps
   *   The start and end timestamps to convert.
   * @param array<string,bool> $options
   *   An array of options that affect how the date string is formatted.
   * @param string|null $fieldtype
   *   Type of field. Example smartdate.
   *
   * @return string
   *   The formatted time string.
   */
  private function getTimeOutput(array $timestamps = [], array $options = [], string $fieldtype = NULL): string {

    /** @var \Drupal\Core\Datetime\DrupalDateTime $start_stamp */
    $start_stamp = $timestamps['start_stamp'];
    /** @var \Drupal\Core\Datetime\DrupalDateTime $end_stamp */
    $end_stamp = $timestamps['end_stamp'];
    $time_end = $time_start_string = $time_end_string = $time_output = '';
    $options = $this->getOptions($options);

    switch ($fieldtype) {
      case 'smartdate':
        if ($options['use_all_day']) {
          if ($start_stamp->format('H:i') === '00:00' && $end_stamp->format('H:i') === '23:59') {
            return $this->t('All Day');
          }
        }
        break;

      default:
        if ($options['use_all_day']) {
          if ($start_stamp->format('H:i') == '00:00' || $start_stamp->format('gia') == $end_stamp->format('gia')) {
            return $this->t('All Day');
          }
        }
        break;
    }

    // Don't display the minutes if it's the top of the hour.
    $time_start = $start_stamp->format('i') == '00' ? 'g' : 'g:i';
    // If same start/end meridians and different start/end time,
    // don't include meridian in start.
    $time_start .= ($start_stamp->format('a') == $end_stamp->format('a') && $start_stamp->format('gia') != $end_stamp->format('gia') ? '' : ' a');

    // Set preformatted start and end times based on.
    // Replace 12:00 am with Midnight & 12:00 pm with Noon.
    switch ($start_stamp->format('H:i')) {
      case '00:00':
        $time_start_string = $options['display_noon_and_midnight'] ? $this->t('midnight') : $start_stamp->format('g a');
        if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
          $time_start_string = ucfirst($time_start_string);
        }
        break;

      case '12:00':
        $time_start_string = $options['display_noon_and_midnight'] ? $this->t('noon') : $start_stamp->format('g a');
        if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
          $time_start_string = ucfirst($time_start_string);
        }
        break;
    }
    if ($start_stamp->format('Hi') != $end_stamp->format('Hi')) {
      $time_end = $end_stamp->format('i') == '00' ? 'g a' : 'g:i a';
      switch ($end_stamp->format('H:i')) {
        case '00:00':
          $time_end_string = $options['display_noon_and_midnight'] ? $this->t('midnight') : $start_stamp->format('g a');
          if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
            $time_end_string = ucfirst($time_end_string);
          }
          break;

        case '12:00':
          $time_end_string = $options['display_noon_and_midnight'] ? $this->t('noon') : $start_stamp->format('g a');
          if ($options['display_noon_and_midnight'] && $options['capitalize_noon_and_midnight']) {
            $time_end_string = ucfirst($time_end_string);
          }
          break;
      }
    }

    $time_output .= $time_start_string ?: $start_stamp->format($time_start);
    if (!empty($time_end)) {
      $time_output .= ($options['separator'] == 'endash' ? ' &ndash; ' : ' to ');
      $time_output .= $time_end_string ?: $end_stamp->format($time_end);
    }

    return str_replace(['am', 'pm'], ['a.m.', 'p.m.'], $time_output);
  }

}
