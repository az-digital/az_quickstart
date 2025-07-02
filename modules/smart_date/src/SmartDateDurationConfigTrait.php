<?php

namespace Drupal\smart_date;

/**
 * Provides a central method to define duration configuration options.
 */
trait SmartDateDurationConfigTrait {

  /**
   * {@inheritdoc}
   */
  public function addDurationConfig(array &$element, array $default_value) {
    $description = '<p>' . $this->t('The possible durations this field can contain. Enter one value per line, in the format key|label.');
    $description .= '<br/>' . $this->t('The key is the stored value, and must be numeric or "custom" to allow an arbitrary length. The label will be used in edit forms.');
    $description .= '<br/>' . $this->t('The label is optional: if a line contains a single number, it will be used as key and label.') . '</p>';

    $element['default_duration_increments'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Allowed duration values'),
      '#description' => $description,
      '#default_value' => $default_value['default_duration_increments'] ?? "30\n60|1 hour\n90\n120|2 hours\ncustom",
      '#required' => TRUE,
    ];

    $element['default_duration'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default duration'),
      '#description' => $this->t('Set which of the duration increments provided above that should be selected by default.'),
      '#default_value' => $default_value['default_duration'] ?? '60',
      '#required' => TRUE,
    ];

    $element['min'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum Date'),
      // @todo Add link to token browser.
      '#description' => $this->t('Minimum date that will be accepted. Leave blank to accept any value. Provide a date value formatted like YYYY-MM-DD or a valid token that will return a date value.'),
      '#default_value' => $default_value['min'] ?? '',
    ];

    $element['max'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Date'),
      // @todo Add link to token browser.
      '#description' => $this->t('Maximum date that will be accepted. Leave blank to accept any value. Provide a date value formatted like YYYY-MM-DD or a valid token that will return a date value.'),
      '#default_value' => $default_value['max'] ?? '',
    ];

    return $element;
  }

}
