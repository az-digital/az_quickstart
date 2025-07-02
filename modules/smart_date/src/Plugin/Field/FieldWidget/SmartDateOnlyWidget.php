<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'smartdate_only' widget.
 *
 * @FieldWidget(
 *   id = "smartdate_only",
 *   label = @Translation("Smart Date | Date-only range"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SmartDateOnlyWidget extends SmartDateInlineWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Hide the time elements.
    $element['time_wrapper']['value']['#date_time_format'] = '';
    $element['time_wrapper']['value']['#date_time_element'] = 'none';
    $element['time_wrapper']['end_value']['#date_time_format'] = '';
    $element['time_wrapper']['end_value']['#date_time_element'] = 'none';

    $element['duration']['#access'] = FALSE;
    $form['#attached']['library'] = ['smart_date/date_only'];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    foreach ($values as &$item) {
      if (isset($item['time_wrapper']['value'])) {
        $item['value'] = $item['time_wrapper']['value'];
      }
      if (isset($item['time_wrapper']['end_value'])) {
        $item['end_value'] = $item['time_wrapper']['end_value'];
      }
      // Force to all day.
      if (!empty($item['value']) && $item['value'] instanceof DrupalDateTime) {
        $item['value']->setTime(0, 0, 0);
      }
      if (!empty($item['end_value']) && $item['value'] instanceof DrupalDateTime) {
        $item['end_value']->setTime(23, 59, 0);
      }
    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
