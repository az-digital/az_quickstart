<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\smart_date\Entity\SmartDateFormat;
use Drupal\smart_date\SmartDatePluginTrait;

/**
 * Template to provide common formatter functionality.
 */
class SmartDateFormatterBase extends DateTimeDefaultFormatter {

  use SmartDatePluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'format' => 'default',
      'force_chronological' => 0,
      'add_classes' => 0,
      'time_wrapper' => 1,
      'localize' => 0,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Use the upstream settings form, which gives us a control to override the
    // timezone.
    $form = parent::settingsForm($form, $form_state);

    // Remove the upstream format_type control, since we want the user to choose
    // a Smart Date Format instead.
    unset($form['format_type']);

    // Change the description of the timezone_override element.
    if (isset($form['timezone_override'])) {
      $form['timezone_override']['#description'] = $this->t('The time zone selected here will be used unless overridden on an individual date.');
    }

    // Ask the user to choose a Smart Date Format.
    $smartDateFormatOptions = $this->getAvailableSmartDateFormatOptions();
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Smart Date Format'),
      '#description' => $this->t('Choose which display configuration to use.'),
      '#default_value' => $this->getSetting('format'),
      '#options' => $smartDateFormatOptions,
    ];

    // Provide an option to force a chronological display.
    $form['force_chronological'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force chronological'),
      '#description' => $this->t('Override any manual sorting or other differences.'),
      '#default_value' => $this->getSetting('force_chronological'),
    ];

    // Provide an option to add spans around the date and time values.
    $form['add_classes'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add classes'),
      '#description' => $this->t('Add classed spans around the time and date values.'),
      '#default_value' => $this->getSetting('add_classes'),
    ];

    // Provide an option to add a time tag around the date and time values.
    $form['time_wrapper'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add time wrapper'),
      '#description' => $this->t('Include an HTML5 time wrapper in the markup. Each part displayed will be individually wrapped.'),
      '#default_value' => $this->getSetting('time_wrapper'),
    ];

    // Provide an option to add spans around the date and time values.
    $form['localize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add Javascript localization'),
      '#description' => $this->t("Automatically shows times in the visitor's timezone."),
      '#default_value' => $this->getSetting('localize'),
      '#states' => [
        // Show this option only if tne time wrapper is enabled.
        'visible' => [
          [':input[name$="[settings_edit_form][settings][time_wrapper]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary['timezone'] = $this->getSetting('timezone_override') === ''
      ? $this->t('No timezone override.')
      : $this->t('Timezone overridden to %timezone.', [
        '%timezone' => $this->getSetting('timezone_override'),
      ]);

    $summary['format'] = $this->t('Smart date format: %format.', [
      '%format' => $this->getSetting('format'),
    ]);

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

    // @phpstan-ignore-next-line
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
