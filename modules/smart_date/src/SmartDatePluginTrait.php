<?php

namespace Drupal\smart_date;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides friendly methods for smart date range.
 */
trait SmartDatePluginTrait {

  use SmartDateTrait, StringTranslationTrait;

  /**
   * The parent entity on which the dates exist.
   *
   * @var mixed
   */
  protected $entity;

  /**
   * The configuration, particularly for the augmenters.
   *
   * @var array
   */
  protected $sharedSettings = [];

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode, $format = '') {
    $field_type = 'smartdate';
    if (property_exists($this, 'fieldDefinition') && $this->fieldDefinition) {
      $field_type = $this->fieldDefinition->getType();
    }
    $elements = [];
    // @todo intelligent switching between retrieval methods.
    // Look for a defined format and use it if specified.
    $format_label = $format ?: $this->getSetting('format');
    if ($format_label) {
      $entity_storage_manager = \Drupal::entityTypeManager()
        ->getStorage('smart_date_format');
      $format = $entity_storage_manager->load($format_label);
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
        'site_time_toggle' => $this->getSetting('site_time_toggle'),
        'allday_label' => $this->getSetting('allday_label'),
      ];
    }
    $timezone_override = $this->getSetting('timezone_override') ?: NULL;
    $add_classes = $this->getSetting('add_classes');
    $time_wrapper = $this->getSetting('time_wrapper');
    $localize = $this->getSetting('localize');
    $parts = $this->getSetting('parts') ?: [
      'start' => 'start',
      'end' => 'end',
      'duration' => 0,
    ];

    // Field settings may not come back as key/value pairs for the parts.
    // Normalize the array to match the expected structure.
    foreach ($parts as $key => $part) {
      if ((bool) $part && $key != $part) {
        $parts[$part] = $part;
        unset($parts[$key]);
      }
    }
    foreach (['start', 'end', 'duration'] as $key) {
      if (!isset($parts[$key])) {
        $parts[$key] = 0;
      }
    }

    $settings['duration'] = $this->getSetting('duration') ?? [
      'separator' => ' | ',
      'unit' => '',
    ];

    $augmenters = $this->initializeAugmenters();
    if ($augmenters) {
      $this->entity = $items->getEntity();
      if (!empty($this->entity->in_preview)) {
        $augmenters = [];
      }
    }

    foreach ($items as $delta => $item) {
      if ($field_type == 'smartdate') {
        if (empty($item->value) || empty($item->end_value)) {
          continue;
        }
        $start_ts = $item->value;
        $end_ts = $item->end_value;
      }
      elseif ($field_type == 'daterange') {
        // Start and end dates are optional, but one of them is required
        // to display anything regardless of what the field thinks.
        if ($item->isEmpty() || (empty($item->start_date) && empty($item->end_date))) {
          continue;
        }
        elseif (empty($item->start_date)) {
          $start_ts = $end_ts = $item->end_date->getTimestamp();
        }
        elseif (empty($item->end_date)) {
          $start_ts = $end_ts = $item->start_date->getTimestamp();
        }
        else {
          $start_ts = $item->start_date->getTimestamp();
          $end_ts = $item->end_date->getTimestamp();
        }
      }
      elseif ($field_type == 'datetime') {
        if (empty($item->date)) {
          continue;
        }
        $start_ts = $end_ts = $item->date->getTimestamp();
      }
      elseif ($field_type == 'timestamp' || $field_type == 'published_at') {
        if (empty($item->value)) {
          continue;
        }
        $start_ts = $end_ts = $item->value;
      }
      else {
        // Not sure how to handle anything else, so return an empty set.
        return $elements;
      }
      $timezone = $item->timezone ? $item->timezone : $timezone_override;
      // Do an all day check before manipulating the range.
      if (static::isAllDay($start_ts, $end_ts)) {
        $all_day = TRUE;
      }
      else {
        $all_day = FALSE;
      }
      // If necessary, format the duration before altering the times.
      $duration_output = '';
      if ($parts['duration']) {
        $duration_output = $this->formatDuration($start_ts, $end_ts, $settings, $timezone);
      }
      // If only one of start and end are displayed, alter accordingly.
      if ($parts['start'] xor $parts['end']) {
        if (in_array('start', $parts)) {
          $end_ts = $start_ts;
        }
        else {
          $start_ts = $end_ts;
        }
      }
      if ($parts['start'] || $parts['end']) {
        $elements[$delta] = static::formatSmartDate($start_ts, $end_ts, $settings, $timezone);
      }
      if ($duration_output) {
        // Fix all day events when showing duration.
        if ($all_day && $elements[$delta]['start']) {
          unset($elements[$delta]['start']['join']);
          unset($elements[$delta]['start']['time']);
        }
        if ($elements[$delta]) {
          $elements[$delta]['spacer'] = ['#markup' => $settings['duration']['separator'] ?? ''];
        }
        $elements[$delta]['duration'] = ['#markup' => $duration_output];
      }
      if ($add_classes) {
        $this->addRangeClasses($elements[$delta]);
      }
      if ($time_wrapper) {
        $this->addTimeWrapper($elements[$delta], $start_ts, $end_ts, $timezone, $add_classes, $localize);
      }
      // Attach the timestamps in case they're needed for later processing.
      $elements[$delta]['#value'] = $start_ts;
      $elements[$delta]['#end_value'] = $end_ts;
      // Get the user/site timezone for comparison.
      $user = \Drupal::currentUser();
      $user_tz = $user->getTimeZone();
      if (!static::isAllDay($start_ts, $end_ts, $timezone) && $settings['site_time_toggle'] && $timezone && $timezone != $user_tz) {
        // Uses a custom timezone, so append time in default timezone.
        $no_date_format = $settings;
        $default_date = \Drupal::service('date.formatter')->format($start_ts, '', $settings['date_format'], $timezone);
        $user_date = \Drupal::service('date.formatter')->format($start_ts, '', $settings['date_format'], $user_tz);
        // If the date is the same in both timezones, only display it once.
        if ($default_date == $user_date) {
          $no_date_format['date_format'] = '';
        }
        $site_time = static::formatSmartDate($start_ts, $end_ts, $no_date_format, $user_tz);
        // Only process further if a value is returned.
        if ($site_time) {
          $event_time = static::formatSmartDate($start_ts, $end_ts, $no_date_format, $timezone);
          // Only append if displayed time will be different.
          if ($site_time != $event_time) {
            $site_time['#prefix'] = ' (';
            $site_time['#suffix'] = ')';
            $elements[$delta]['site_time'] = $site_time;
          }
        }
      }

      if (!empty($item->_attributes)) {
        $elements[$delta]['#attributes'] += $item->_attributes;
        // Unset field item attributes since they have been included in the
        // formatter output and should not be rendered in the field template.
        unset($item->_attributes);
      }

      if (!empty($augmenters['instances'])) {
        // @todo examine why we aren't using the $start_ts and $end_ts that are
        // already normalized above.
        $this->augmentOutput($elements[$delta], $augmenters['instances'], $item->value, $item->end_value, $timezone, $delta);
      }
    }

    // If specified, sort based on start, end times.
    if ($this->getSetting('force_chronological')) {
      $elements = smart_date_array_orderby($elements, '#value', SORT_ASC, '#end_value', SORT_ASC);
    }

    return $elements;
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
    ];
  }

  /**
   * Use provided configuration to retrieve a list of date augmenters.
   *
   * @param array $keys
   *   Optional array to allow multiple sets of augmenter configurations.
   *
   * @return array
   *   An array of the available augmenters.
   */
  protected function initializeAugmenters(array $keys = ['instances']) {
    if (empty(\Drupal::hasService('plugin.manager.dateaugmenter'))) {
      return [];
    }
    $config = [];
    if (method_exists($this, 'getThirdPartySettings')) {
      $config = $this->getThirdPartySettings('date_augmenter');
    }
    // Retrieve legacy configuration not stored in 'instances' (#3399475).
    if (is_array($config) && !isset($config['instances'])) {
      $instances = $config;
      $config = [
        'instances' => $instances,
      ];
    }
    $this->sharedSettings = $config;
    $dateAugmenterManager = \Drupal::service('plugin.manager.dateaugmenter');
    // @todo Support custom entities.
    if ($keys) {
      $augmenters = [];
      foreach ($keys as $key) {
        $key_config = $config[$key] ?? NULL;
        $augmenters[$key] = $dateAugmenterManager->getActivePlugins($key_config);
      }
    }
    else {
      $augmenters['instances'] = $dateAugmenterManager->getActivePlugins($config);
    }
    return $augmenters;
  }

  /**
   * Apply any configured augmenters.
   *
   * @param array $output
   *   Render array of output.
   * @param array $augmenters
   *   The augmenters that have been configured.
   * @param int $start_ts
   *   The start of the date range.
   * @param int $end_ts
   *   The end of the date range.
   * @param string $timezone
   *   The timezone to use.
   * @param int $delta
   *   The field delta being formatted.
   * @param string $type
   *   The set of configuration to use.
   * @param string $repeats
   *   An optional RRULE string containing recurrence details.
   * @param string $ends
   *   An optional timestamp to specify the end of the last instance.
   */
  protected function augmentOutput(array &$output, array $augmenters, $start_ts, $end_ts, $timezone, $delta, $type = 'instances', $repeats = '', $ends = '') {
    if (!$augmenters) {
      return;
    }

    if (!is_numeric($start_ts)) {
      $start_ts = strtotime($start_ts);
    }

    if (!is_numeric($end_ts)) {
      $end_ts = ($end_ts !== NULL) ? strtotime($end_ts) : $start_ts;
    }

    foreach ($augmenters as $augmenter_id => $augmenter) {
      // Fallback for outdated schema.
      if ($type === 'instances' && !isset($this->sharedSettings[$type])) {
        $type = '';
      }
      if (!empty($type)) {
        $settings = $this->sharedSettings[$type]['settings'][$augmenter_id] ?? [];
      }
      else {
        $settings = $this->sharedSettings['settings'][$augmenter_id] ?? [];
      }

      $augmenter->augmentOutput(
        $output,
        DrupalDateTime::createFromTimestamp($start_ts),
        DrupalDateTime::createFromTimestamp($end_ts),
        [
          'timezone' => $timezone,
          'allday' => static::isAllDay($start_ts, $end_ts, $timezone),
          'entity' => $this->entity,
          'settings' => $settings,
          'delta' => $delta,
          'formatter' => $this,
          'repeats' => $repeats,
          'ends' => empty($ends) ? $ends : DrupalDateTime::createFromTimestamp($ends),
          'field_name' => $this->fieldDefinition->getName(),
        ]
      );
    }
  }

}
