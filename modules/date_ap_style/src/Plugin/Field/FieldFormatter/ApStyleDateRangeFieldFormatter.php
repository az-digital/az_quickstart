<?php

namespace Drupal\date_ap_style\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime_range\DateTimeRangeTrait;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 *
 * @FieldFormatter(
 *   id = "daterange_ap_style",
 *   label = @Translation("AP Style"),
 *   field_types = {
 *     "daterange",
 *     "smartdate",
 *   }
 * )
 */
class ApStyleDateRangeFieldFormatter extends ApSelectFormatterBase {

  use DateTimeRangeTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    $config = \Drupal::config('date_ap_style.dateapstylesettings');
    $base_defaults = [
      'separator' => $config->get('separator') ?? 'to',
    ];
    return $base_defaults + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $elements = parent::settingsForm($form, $form_state);

    $elements['separator'] = [
      '#type' => 'select',
      '#title' => $this->t('Date range separator'),
      '#options' => [
        'endash' => $this->t('En dash'),
        'to' => $this->t('to'),
      ],
      '#default_value' => $this->getSetting('separator'),
    ];
    $elements['month_only']['#description'] = $this->t('Shows only the month (e.g., Aug.) for the date, excluding the day. Year shown as required.');

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();

    if ($this->getSetting('separator') == 'endash') {
      $summary[] = $this->t('Using en dash date range separator');
    }
    else {
      $summary[] = $this->t('Using "to" date range separator');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    return parent::buildViewElements($items, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  protected function processItem($item, $options, $timezone, $langcode, $field_type): array {
    $dates = [];

    if (!empty($item->start_date) && !empty($item->end_date)) {
      $start_date = $item->start_date;
      $end_date = $item->end_date;
      $dates['start'] = $start_date->getTimestamp();
      $dates['end'] = $end_date->getTimestamp();
    }

    if ($field_type === 'smartdate') {
      if (!empty($item->value) && !empty($item->end_value)) {
        $dates['start'] = $item->value;
        $dates['end'] = $item->end_value;
      }
    }

    if (isset($dates['start']) && isset($dates['end'])) {
      return [
        '#cache' => [
          'contexts' => [
            'timezone',
          ],
        ],
        '#markup' => $this->apStyleDateFormatter->formatRange($dates, $options, $timezone, $langcode, $field_type),
      ];
    }

    return [];
  }

}
