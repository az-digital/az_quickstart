<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_date\SmartDatePluginTrait;

/**
 * Plugin implementation of the 'Custom' formatter for 'smartdate' fields.
 *
 * This formatter renders the time range as plain text, with a fully
 * configurable date format using the PHP date syntax and separator.
 *
 * @FieldFormatter(
 *   id = "smartdate_custom",
 *   label = @Translation("Smart Date | Custom"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDateCustomFormatter extends TimestampFormatter {

  use SmartDatePluginTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => ' - ',
      'join' => ', ',
      'time_format' => 'g:ia',
      'time_hour_format' => 'ga',
      'date_format' => 'D, M j Y',
      'allday_label' => 'All day',
      'date_first' => '1',
      'ampm_reduce' => '1',
      'site_time_toggle' => '1',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    // Don't need format_type provided by parent, so unset.
    unset($form['format_type']);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Time separator'),
      '#description' => $this->t('The string to separate the start and end times. Include spaces before and after if those are desired.'),
      '#default_value' => $this->getSetting('separator'),
    ];

    $form['join'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date/time join'),
      '#description' => $this->t('The characters that will be used to join dates and their associated times.'),
      '#default_value' => $this->getSetting('join'),
    ];

    $form['time_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Time Format'),
      '#description' => $this->t('The PHP date code to use for formatting times.'),
      '#default_value' => $this->getSetting('time_format'),
    ];

    $form['time_hour_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Time Format - on the hour'),
      '#description' => $this->t('The PHP date code to use for formatting times that fall on the hour. Examples might be 2pm or 14h. Leave this blank to always use the standard format specified above.'),
      '#default_value' => $this->getSetting('time_hour_format'),
    ];

    $form['date_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PHP Date Format'),
      '#description' => $this->t('The PHP date code to use for formatting dates.'),
      '#default_value' => $this->getSetting('date_format'),
    ];

    $form['allday_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('All Day Label'),
      '#description' => $this->t('What to output when an event has been set to run all day. Leave blank to only show the date.'),
      '#default_value' => $this->getSetting('allday_label'),
    ];

    $form['date_first'] = [
      '#type' => 'select',
      '#title' => $this->t('First part shown'),
      '#description' => $this->t('Specify whether the time or date should be shown first.'),
      '#default_value' => $this->getSetting('first'),
      '#options' => [
        '1' => $this->t('Date'),
        '0' => $this->t('Time'),
      ],
    ];

    $form['ampm_reduce'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reduce AM/PM display'),
      '#description' => $this->t("Don't show am/pm in the start time if it's the same as the value for the end time, in the same day. Note that this is recommended by the Associated Press style guide."),
      '#default_value' => $this->getSetting('ampm_reduce'),
    ];

    $form['site_time_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn off site time display'),
      '#description' => $this->t("Don't show default site time in parentheses at end of the value."),
      '#default_value' => $this->getSetting('site_time_toggle'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    if ($join = $this->getSetting('join')) {
      $summary[] = $this->t('Join: %join', ['%join' => $join]);
    }

    if ($time_format = $this->getSetting('time_format')) {
      $summary[] = $this->t('Time Format: %time_format', ['%time_format' => $time_format]);
    }

    if ($date_format = $this->getSetting('date_format')) {
      $summary[] = $this->t('Date Format: %date_format', ['%date_format' => $date_format]);
    }

    return $summary;
  }

}
