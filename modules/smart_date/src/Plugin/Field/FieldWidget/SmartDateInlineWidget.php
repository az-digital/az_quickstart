<?php

namespace Drupal\smart_date\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'smartdate_inline' widget.
 *
 * @FieldWidget(
 *   id = "smartdate_inline",
 *   label = @Translation("Smart Date | Inline range"),
 *   field_types = {
 *     "smartdate",
 *     "daterange"
 *   }
 * )
 */
class SmartDateInlineWidget extends SmartDateDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => 'to',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!isset($element['value']) || (isset($element['#access']) && $element['#access'] === FALSE)) {
      return $element;
    }

    $time_wrapper = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['smartdate--time-inline'],
      ],
      '#tree' => TRUE,
    ];
    $element = array_merge(['time_wrapper' => $time_wrapper], $element);
    // Move the start and end elements into our new container.
    $element['time_wrapper']['value'] = $element['value'];
    $separator = empty($this->getSetting('separator')) ? $this->t('to') : $this->getSetting('separator');
    $element['time_wrapper']['separator']['#markup'] = '<span class="smartdate--separator">' . $separator . '</span>';
    $element['time_wrapper']['end_value'] = (isset($element['end_value'])) ? $element['end_value'] : $element['value'];
    unset($element['value']);
    unset($element['end_value']);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings_form = parent::settingsForm($form, $form_state);

    $settings_form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator string (separating start and end date)'),
      '#default_value' => $this->getSetting('separator'),
    ];

    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if (!empty($this->getSetting('separator'))) {
      $summary[] = $this->t('Separator string (separating start and end date): @separator', ['@separator' => $this->getSetting('separator')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    // The widget form element type has transformed the value to a
    // DrupalDateTime object at this point. We need to convert it back to the
    // storage timestamp.
    foreach ($values as &$item) {
      if (isset($item['time_wrapper']['value'])) {
        $item['value'] = $item['time_wrapper']['value'];
      }
      if (isset($item['time_wrapper']['end_value'])) {
        $item['end_value'] = $item['time_wrapper']['end_value'];
      }
    }
    $values = parent::massageFormValues($values, $form, $form_state);
    return $values;
  }

}
