<?php

namespace Drupal\smart_date_recur\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\Plugin\Field\FieldFormatter\SmartDateDefaultFormatter;
use Drupal\smart_date_recur\Entity\SmartDateRule;
use Drupal\smart_date_recur\SmartDateRecurPluginTrait;

/**
 * Plugin for a recurrence-optimized formatter for 'smartdate' fields.
 *
 * This formatter renders the start time range using <time> elements, with
 * recurring dates given special formatting.
 *
 * @FieldFormatter(
 *   id = "smartdate_recurring",
 *   label = @Translation("Smart Date | Recurring"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateRecurrenceFormatter extends SmartDateDefaultFormatter {

  use SmartDateRecurPluginTrait;
  use MessengerTrait;

  /**
   * The formatter configuration.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'past_display' => '2',
      'upcoming_display' => '2',
      'show_next' => FALSE,
      'current_upcoming' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    // Ask the user to choose how many past and upcoming instances to display.
    $form['past_display'] = [
      '#type' => 'number',
      '#title' => $this->t('Recent Instances'),
      '#description' => $this->t('Specify how many recent instances to display'),
      '#default_value' => $this->getSetting('past_display'),
    ];

    $form['upcoming_display'] = [
      '#type' => 'number',
      '#title' => $this->t('Upcoming Instances'),
      '#description' => $this->t('Specify how many upcoming instances to display'),
      '#default_value' => $this->getSetting('upcoming_display'),
    ];

    $form['show_next'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show next instance separately'),
      '#description' => $this->t('Isolate the next instance to make it more obvious'),
      '#default_value' => $this->getSetting('show_next'),
      '#states' => [
        // Show this option only if at least one upcoming value will be shown.
        'invisible' => [
          [':input[name$="[settings_edit_form][settings][upcoming_display]"]' => ['filled' => FALSE]],
          [':input[name$="[settings_edit_form][settings][upcoming_display]"]' => ['value' => '0']],
        ],
      ],
    ];

    $form['current_upcoming'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Treat current events as upcoming'),
      '#description' => $this->t('Otherwise, they will be treated as being in the past.'),
      '#default_value' => $this->getSetting('current_upcoming'),
    ];

    $form['force_chronological']['#description'] = $this->t('Merge together all recurring rule instances and single events, and sort chronologically before subsetting as a single group.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->getSetting('timezone_override') === ''
      ? $this->t('No timezone override.')
      : $this->t('Timezone overridden to %timezone.', [
        '%timezone' => $this->getSetting('timezone_override'),
      ]);

    $summary[] = $this->t('Smart date format: %format.', [
      '%format' => $this->getSetting('format'),
    ]);

    return $summary;
  }

  /**
   * Explicitly declare support for the Date Augmenter API.
   *
   * @return array
   *   The keys and labels for the sets of configuration.
   */
  public function supportsDateAugmenter() {
    // Return an array of configuration sets to use.
    return [
      'instances' => $this->t('Individual Dates'),
      'rule' => $this->t('Recurring Rule'),
    ];
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
    $force_chrono = $this->getSetting('force_chronological') ?: FALSE;
    $settings['timezone_override'] = $this->getSetting('timezone_override') ?: NULL;
    $settings['add_classes'] = $this->getSetting('add_classes');
    $settings['time_wrapper'] = $this->getSetting('time_wrapper');
    $settings['past_display'] = $this->getSetting('past_display');
    $settings['upcoming_display'] = $this->getSetting('upcoming_display');
    $settings['show_next'] = $this->getSetting('show_next');
    $settings['current_upcoming'] = $this->getSetting('current_upcoming');

    // If an exposed filter is set for the field, use that as the minimum date.
    // @phpstan-ignore-next-line
    $filter = \Drupal::request()->query->get($items->getName());
    if (is_array($filter) && isset($filter['min']) && strtotime($filter['min']) > 0) {
      $settings['min_date'] = strtotime($filter['min']);
    }
    elseif (is_string($filter) && strtotime($filter) > 0) {
      $settings['min_date'] = strtotime($filter);
    }

    // Retrieve any available augmenters.
    $augmenter_sets = ['instances', 'rule'];
    $augmenters = $this->initializeAugmenters($augmenter_sets);
    // Entity only needed if there are augmenters to process.
    if (count($augmenters, COUNT_RECURSIVE) > 2) {
      $this->entity = $items->getEntity();
    }
    $settings['augmenters'] = $augmenters;
    $this->settings = $settings;

    $rrules = [];
    foreach ($items as $delta => $item) {
      $timezone = $item->timezone ? $item->timezone : $settings['timezone_override'];
      if (empty($item->value) || empty($item->end_value)) {
        continue;
      }
      // Save the original delta within the item.
      $item->delta = $delta;
      if (empty($item->rrule) || $force_chrono) {
        if ($force_chrono) {
          $elements[$item->value] = $item;
        }
        else {
          // No rule so include the item directly.
          $elements[$delta] = $this->buildOutput($delta, $item, $settings);
        }
      }
      else {
        // Uses a rule, so use a placeholder instead.
        if (!isset($rrules[$item->rrule])) {
          $elements[$delta]['#rrule'] = $item->rrule;
          $rrules[$item->rrule]['delta'] = $delta;
        }
        // Add this instance to our array of instances for the rule.
        $rrules[$item->rrule]['instances'][] = $item;
      }
    }
    if ($force_chrono) {
      ksort($elements);
      $elements = array_values($elements);
      $next_index = $this->findNextInstance($elements, $settings);
      return [$this->subsetInstances($elements, $next_index, $settings)];
    }
    foreach ($rrules as $rrid => $rrule_collected) {
      if (empty($rrule_collected['instances'])) {
        continue;
      }
      $instances = $rrule_collected['instances'];
      $delta = $rrule_collected['delta'];
      // Retrieve the text of the rrule.
      $rrule = SmartDateRule::load($rrid);
      if (empty($rrule)) {
        $this->messenger()->addError($this->t('One or dates reference a missing recurring rule: %rrid', ['%rrid' => $rrid]));
        // Unable to load the rrule, so render all the date instances directly.
        // Flag that this is a "pseudo" render for preprocessors to work with.
        $elements[$delta]['#smart_date_recur_no_rrule'] = TRUE;
        foreach ($instances as $key => $instance) {
          $elements[$delta][$key] = [
            '#prefix' => '<div>',
            '#suffix' => '</div>',
            'content' => $this->buildOutput($delta, $instance, $settings),
          ];
        }
        continue;
      }

      if (in_array($rrule->get('freq')->getString(), ['MINUTELY', 'HOURLY'])) {
        $within_day = TRUE;
      }
      else {
        $within_day = FALSE;
      }
      $next_index = $this->findNextInstance($instances, $settings);

      if ($within_day) {
        // Output for dates recurring within a day.
        // Group the instances into days first.
        $instance_dates = [];
        $instances_nested = [];
        $comparison_format = $this->settingsFormatNoTime($settings);
        $comparison_format['date_format'] = 'Ymd';
        // Group instances into days, make array of dates.
        foreach ($instances as $instance) {
          $this_comparison_date = static::formatSmartDate($instance->value, $instance->end_value, $comparison_format, $instance->timezone ?? NULL, 'string');
          // Keep the dates in a keyed array to match the nested array.
          $instance_dates[$this_comparison_date] = (int) $this_comparison_date;
          // Keep all the instances in a nested array.
          $instances_nested[$this_comparison_date][] = $instance;
        }
        // Strip the existing keys to allow comparison functions to work on
        // simple, consecutive number keys.
        $instances_nested = array_values($instances_nested);
        $instance_dates = array_values($instance_dates);
        $now = time();
        $timezone = date_default_timezone_get();
        $today = (int) static::formatSmartDate($now, $now, $comparison_format, $timezone, 'string');
        // The $instance_dates array is used only to find the next index.
        $next_index_nested = $this->findNextInstanceByDay($instance_dates, $today);
        $rrule_output = $this->subsetInstances($instances_nested, $next_index_nested, $settings, $within_day);
      }
      else {
        // Output for other recurrences frequencies.
        // Find the 'next' instance after now.
        $rrule_output = $this->subsetInstances($instances, $next_index, $settings, $within_day);
      }

      $rrule_output['#rule_text']['rule'] = $rrule->getTextRule();
      if (!empty($augmenters['rule'])) {
        $repeats = $rrule->getRule();
        $start = $instances[0]->getValue();
        // Grab the end value of the last instance.
        $ends = $instances[array_key_last($instances)]->getValue()['end_value'];
        $this->augmentOutput($rrule_output['#rule_text'], $augmenters['rule'], $start['value'], $start['end_value'], $start['timezone'], $delta, 'rule', $repeats, $ends);
      }

      if ($next_index == -1) {
        $next_instance = array_pop($instances)->getValue();
      }
      else {
        $next_instance = $instances[$next_index]->getValue();
      }
      // @phpstan-ignore-next-line
      $rrule_output['#cache']['max-age'] = $next_instance['value'] - \Drupal::time()->getRequestTime();

      $elements[$delta] = $rrule_output;
    }

    return $elements;
  }

}
