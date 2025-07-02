<?php

namespace Drupal\smart_date\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimePlainFormatter;
use Drupal\smart_date\SmartDateTrait;

/**
 * Plugin implementation of the 'Plain' formatter for 'smartdate' fields.
 *
 * This formatter renders the data range as a plain text string, with a
 * configurable separator using an ISO-like date format string.
 *
 * @FieldFormatter(
 *   id = "smartdate_plain",
 *   label = @Translation("Smart Date | Plain"),
 *   field_types = {
 *     "smartdate"
 *   }
 * )
 */
class SmartDatePlainFormatter extends DateTimePlainFormatter {

  use SmartDateTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      $item->start_time = isset($items[$delta]->value) ? DrupalDateTime::createFromTimestamp($items[$delta]->value) : '';
      $item->end_time = isset($items[$delta]->end_value) ? DrupalDateTime::createFromTimestamp($items[$delta]->end_value) : '';
      if (!empty($item->start_time) && !empty($item->end_time)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_time */
        $start_time = $item->start_time;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_time */
        $end_time = $item->end_time;

        if ($start_time->getTimestamp() !== $end_time->getTimestamp()) {
          $elements[$delta] = [
            'start_time' => $this->buildDate($start_time),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_time' => $this->buildDate($end_time),
          ];
        }
        else {
          $elements[$delta] = $this->buildDate($start_time);

          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not render in the field template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
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

    return $summary;
  }

}
