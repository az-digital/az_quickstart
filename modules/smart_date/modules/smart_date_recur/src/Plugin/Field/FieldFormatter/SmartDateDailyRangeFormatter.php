<?php

namespace Drupal\smart_date_recur\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\Plugin\Field\FieldFormatter\SmartDateDefaultFormatter;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Drupal\smart_date_recur\SmartDateRecurPluginTrait;

/**
 * Plugin for a recurrence-optimized formatter for 'smartdate' fields.
 *
 * This formatter is similar to the default formatter but renders
 * consecutive daily ranges as a single line.
 *
 * @FieldFormatter(
 *   id = "smartdate_dailyrange",
 *   label = @Translation("Smart Date | Daily Range"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateDailyRangeFormatter extends SmartDateDefaultFormatter {

  use SmartDateRecurPluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode, $format = '') {
    $elements = [];
    // @todo intelligent switching between retrieval methods
    // Look for a defined format and use it if specified.
    $format_label = $this->getSetting('format');
    if ($format_label) {
      $format = SmartDateFormat::load($format_label);
      $settings = $format->getOptions();
    }
    else {
      $settings = [
        'separator' => $this->getSetting('separator'),
        'join' => $this->getSetting('join'),
        'time_format' => $this->getSetting('time_format'),
        'time_hour_format' => $this->getSetting('time_hour_format'),
        'date_format' => $this->getSetting('date_format'),
        'date_first' => $this->getSetting('date_first'),
        'ampm_reduce' => $this->getSetting('ampm_reduce'),
        'allday_label' => $this->getSetting('allday_label'),
      ];
    }
    $add_classes = $this->getSetting('add_classes');
    $time_wrapper = $this->getSetting('time_wrapper');
    $rrules = [];
    $rrules_nondaily = [];

    $augmenters = $this->initializeAugmenters();
    if ($augmenters) {
      $this->entity = $items->getEntity();
    }

    foreach ($items as $delta => $item) {
      if (empty($item->value) || empty($item->end_value)) {
        continue;
      }
      $is_daily = FALSE;
      if (!empty($item->rrule)) {
        if (isset($rrules[$item->rrule])) {
          // Already established as daily.
          $is_daily = TRUE;
        }
        elseif (isset($rrules_nondaily[$item->rrule])) {
          // Already established as NOT daily, so list as normal.
        }
        else {
          // New rule to process, so load it.
          $rrule_obj = SmartDateRule::load($item->rrule);
          if ($rrule_obj !== NULL && is_object($rrule_obj)) {
            $rule_props = $rrule_obj->toArray();
          }
          $allowed_freq = ['HOURLY', 'MINUTELY'];
          // Check that no extra parameters have been set.
          // @todo Separate handling for daily ranges with no end?
          // @todo Check for overrides.
          if (!empty($rule_props['freq'])) {
            if ($rule_props['freq'][0]['value'] == 'DAILY' && $rule_props['limit'] && !$rule_props['parameters']) {
              $is_daily = TRUE;
            }
            elseif (in_array($rule_props['freq'][0]['value'], $allowed_freq)) {
              $is_daily = TRUE;
            }
          }

          if ($is_daily) {
            // Uses a daily rule, so render a range instead.
            $is_daily = TRUE;
            $elements[$delta] = $item->rrule;
            $rrules[$item->rrule]['delta'] = $delta;
            $rrules[$item->rrule]['freq'] = $rule_props['freq'][0]['value'] ?? '';
          }
          else {
            $rrules_nondaily[$item->rrule]['delta'] = $delta;
          }
        }
      }
      if ($is_daily) {
        // Add this instance to our array of instances for the rule.
        $rrules[$item->rrule]['instances'][] = $item;
      }
      else {
        // No rule so include the item directly.
        $timezone = $item->timezone ?: NULL;
        $elements[$delta] = static::formatSmartDate($item->value, $item->end_value, $settings, $timezone);
        if ($add_classes) {
          $this->addRangeClasses($elements[$delta]);
        }
        if ($time_wrapper) {
          $this->addTimeWrapper($elements[$delta], $item->value, $item->end_value, $timezone);
        }

        if ($augmenters) {
          $this->augmentOutput($elements[$delta], $augmenters, $item->value, $item->end_value, $timezone, $delta);
        }
      }
    }
    $output = [];
    foreach ($rrules as $rrule_collected) {
      if (empty($rrule_collected['instances'])) {
        continue;
      }
      $instances = $rrule_collected['instances'];
      $settings_notime = $this->settingsFormatNoTime($settings);
      $settings_nodate = $this->settingsFormatNoDate($settings);
      switch ($rrule_collected['freq']) {
        case 'DAILY':
          $first_date = array_shift($instances);
          $last_date = array_pop($instances);
          $timezone = $first_date->timezone ?: NULL;
          $output['time'] = static::formatSmartDate($first_date->value, $first_date->end_value, $settings_notime, $timezone);
          $output['join'] = ['#markup' => $settings['join']];
          $output['date'] = static::formatSmartDate($first_date->value, $last_date->end_value, $settings_nodate, $timezone);
          $output['#attributes']['class'] = ['smart-date--range'];
          $output = $this->massageForOutput($output, $settings, $add_classes);
          break;

        case 'HOURLY':
        case 'MINUTELY':
          // For recurrences within a day, display in a succinct format.
          $output = [];
          $times = [];
          // Group instances into days.
          foreach ($instances as $instance) {
            $this_formatted_date = static::formatSmartDate($instance->value, $instance->end_value, $settings_notime, $instance->timezone ?? NULL, 'string');
            $times[$this_formatted_date][] = $instance;
          }
          $output = $this->formatWithinDay($times, $settings);
          break;
      }
      $delta = $rrule_collected['delta'];
      $elements[$delta] = $output;
    }

    return $elements;
  }

}
