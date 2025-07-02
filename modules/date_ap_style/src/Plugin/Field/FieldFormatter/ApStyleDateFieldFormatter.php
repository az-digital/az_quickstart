<?php

namespace Drupal\date_ap_style\Plugin\Field\FieldFormatter;

/**
 * Plugin implementation of the 'timestamp' formatter as time ago.
 *
 * @FieldFormatter(
 *   id = "timestamp_ap_style",
 *   label = @Translation("AP Style"),
 *   field_types = {
 *     "datetime",
 *     "timestamp",
 *     "created",
 *     "changed",
 *     "published_at",
 *   }
 * )
 */
class ApStyleDateFieldFormatter extends ApSelectFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function processItem($item, $options, $timezone, $langcode, $field_type) {
    if ($field_type == 'datetime') {
      $timestamp = $item->date->getTimestamp();
    }
    else {
      $timestamp = $item->value;
    }

    return [
      '#cache' => [
        'contexts' => [
          'timezone',
        ],
      ],
      '#markup' => $this->apStyleDateFormatter->formatTimestamp($timestamp, $options, $timezone, $langcode),
    ];
  }

}
