<?php

namespace Drupal\az_card_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'az_card_default' widget.
 *
 * @FieldWidget(
 *   id = "az_card_default",
 *   module = "az_card_field",
 *   label = @Translation("Card"),
 *   field_types = {
 *     "az_card"
 *   }
 * )
 */
class AZCardWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $element['az_card'] = [
      '#type' => 'details',
      '#title' => $this->t('Card %number', ['%number' => $delta + 1]),
    ];
    $element['az_card']['media_id'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
      '#title' => $this->t('Media'),
      '#default_value' => isset($items[$delta]->image) ? $items[$delta]->image : 0,
    ];
    $element['az_card']['title'] = [
      '#title' => 'Title',
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
      '#size' => '60',
      '#placeholder' => '',
      '#maxlength' => 255,
    ];
    $element['az_card']['body'] = [
      '#title' => 'Body',
      '#type' => 'text_format',
      '#default_value' => isset($items[$delta]->body) ? $items[$delta]->body : NULL,
      '#format' => $items[$delta]->body_format ?? 'basic_html',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // This loop is through (potential) field instances.
    $return = [];
    foreach ($values as $delta => $wrapper) {
      $value = $wrapper['az_card'];
      if (empty($value['media_id'])) {
        // A null media value should be saved as 0.
        $value['media_id'] = 0;
      }
      // // Options are stored as a serialized array.
      // if (!empty($value['options'])) {
      //   foreach ($value['options'] as $key => $option) {
      //     if (empty($option)) {
      //       // Remove empty options.
      //       unset($value['options'][$key]);
      //     }
      //   }
      //   // Don't serialize an empty array.
      //   if (!empty($value['options'])) {
      //     $value['options'] = serialize($value['options']);
      //   }
      //   else {
      //     unset($value['options']);
      //   }
      // }
      $value['body'] = $value['body']['value'];
      $value['body_format'] = $value['body']['format'];
      $return[$delta] = $value;
    }
    return $return;
  }

}
