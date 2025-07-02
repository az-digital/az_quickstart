<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_date\Entity\SmartDateFormat;

/**
 * Plugin implementation of the 'Default' formatter for 'smartdate' fields.
 *
 * This formatter renders the time range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "smartdate_default",
 *   label = @Translation("Smart Date | Default"),
 *   field_types = {
 *     "smartdate",
 *     "daterange",
 *     "datetime",
 *     "timestamp",
 *     "published_at"
 *   }
 * )
 */
class SmartDateDefaultFormatter extends SmartDateFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'parts' => ['start', 'end'],
      'duration' => [
        'separator' => ' | ',
        'unit' => '',
        'decimals' => 2,
        'suffix' => 'h',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  protected function getPartLabels() {
    return [
      'start' => $this->t('Start'),
      'end' => $this->t('End'),
      'duration' => $this->t('Duration'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    $form['parts'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Time Parts'),
      '#description' => $this->t('Which parts of the time and range range should be output.'),
      '#default_value' => $this->getSetting('parts') ?? ['start', 'end'],
      '#options' => $this->getPartLabels(),
      '#weight' => -10,
      '#required' => TRUE,
    ];

    // Change the description of the timezone_override element.
    if (isset($form['timezone_override'])) {
      $form['timezone_override']['#states'] = [
        // Show this option only if the units will be hours.
        'invisible' => [
          ':input[name$="[settings_edit_form][settings][parts][start]"]' => ['checked' => FALSE],
          ':input[name$="[settings_edit_form][settings][parts][end]"]' => ['checked' => FALSE],
        ],
      ];
      $form['timezone_override']['#weight'] = -9;
    }

    $form['format']['#states'] = [
      // Show this option only if the units will be hours.
      'invisible' => [
        ':input[name$="[settings_edit_form][settings][parts][start]"]' => ['checked' => FALSE],
        ':input[name$="[settings_edit_form][settings][parts][end]"]' => ['checked' => FALSE],
      ],
    ];
    $form['format']['#weight'] = -8;

    $form['duration'] = [
      '#type' => 'details',
      '#title' => $this->t('Duration'),
      '#description' => $this->t('How the duration should be formatted.'),
      // Controls the HTML5 'open' attribute. Defaults to FALSE.
      '#open' => TRUE,
      '#states' => [
        // Show this option only if the units will be hours.
        'visible' => [
          ':input[name$="[settings_edit_form][settings][parts][duration]"]' => ['checked' => TRUE],
        ],
      ],
      '#weight' => -7,
    ];
    $duration_settings = $this->getSetting('duration');

    $form['duration']['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#description' => $this->t('Specify what characters should be used to separate the duration from the time.'),
      '#default_value' => $duration_settings['separator'],
    ];

    $form['duration']['unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#description' => $this->t('Specify what units will be used to show the duration. Auto will use a combination of units, up to 2 (e.g. hours and minutes).'),
      '#options' => [
        '' => $this->t('Auto (Drupal default)'),
        'h' => $this->t('Hours'),
        'm' => $this->t('Minutes'),
      ],
      '#default_value' => $duration_settings['unit'],
    ];

    $form['duration']['decimals'] = [
      '#type' => 'number',
      '#title' => $this->t('Decimals'),
      '#description' => $this->t('Maximum number of decimals to show.'),
      '#default_value' => $duration_settings['decimals'],
      '#states' => [
        // Show this option only if the units will be hours.
        'visible' => [
          ':input[name$="[settings_edit_form][settings][duration][unit]"]' => ['value' => 'h'],
        ],
      ],
    ];

    $form['duration']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#description' => $this->t('Characters to show after the calculated number.'),
      '#default_value' => $duration_settings['suffix'],
      '#states' => [
        // Show this option only if at least one upcoming value will be shown.
        'invisible' => [
          ':input[name$="[settings_edit_form][settings][duration][unit]"]' => ['value' => ''],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $labels = $this->getPartLabels();
    $parts = $this->getSetting('parts');
    if (is_array($parts)) {
      $labelled_parts = [];
      foreach ($parts as $part) {
        if (!empty($labels[$part])) {
          $labelled_parts[] = $labels[$part];
        }
      }

      $summary['parts'] = $this->t('Display: %parts.', [
        '%parts' => implode(', ', $labelled_parts),
      ]);
    }

    return $summary;
  }

  /**
   * Get an array of available Smart Date format options.
   *
   * @return string[]
   *   An array of Smart Date Format machine names keyed to Smart Date Format
   *   names, suitable for use in an #options array.
   */
  protected function getAvailableSmartDateFormatOptions() {
    $formatOptions = [];

    $smartDateFormats = \Drupal::entityTypeManager()
      ->getStorage('smart_date_format')
      ->loadMultiple();

    foreach ($smartDateFormats as $type => $format) {
      if ($format instanceof SmartDateFormat) {
        $formatted = static::formatSmartDate(time(), time() + 3600, $format->getOptions(), NULL, 'string');
        $formatOptions[$type] = $format->label() . ' (' . $formatted . ')';
      }
    }

    return $formatOptions;
  }

}
