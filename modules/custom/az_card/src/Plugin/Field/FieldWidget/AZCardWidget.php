<?php

namespace Drupal\az_card\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'az_card' field widget.
 *
 * @FieldWidget(
 *   id = "az_card",
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

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Body'),
      '#default_value' => isset($items[$delta]->body) ? $items[$delta]->body : NULL,
      '#format' => $items[$delta]->body_format ?? 'basic_html',
    ];

    $element['media'] = [
      '#type' => 'media_library',
      '#title' => $this->t('Card Media'),
      '#default_value' => isset($items[$delta]->media) ? $items[$delta]->media : NULL,
      '#allowed_bundles' => ['az_image'],
      '#delta' => $delta,
      '#cardinality' => 1,
    ];

    $element['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Link Title'),
      '#default_value' => isset($items[$delta]->link_title) ? $items[$delta]->link_title : NULL,
    ];

    $element['link_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Card Link URI'),
      '#default_value' => isset($items[$delta]->link_uri) ? $items[$delta]->link_uri : NULL,
    ];

    // TODO: card style(s) selection form.
    $element['options'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Card Options'),
      '#default_value' => isset($items[$delta]->options) ? $items[$delta]->options : NULL,
      // Hide element until implemented.
      '#access' => FALSE,
    ];

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-card-elements';
    $element['#attached']['library'][] = 'az_card/az_card';

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    return isset($violation->arrayPropertyPath[0]) ? $element[$violation->arrayPropertyPath[0]] : $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $delta => $value) {
      if ($value['title'] === '') {
        $values[$delta]['title'] = NULL;
      }
      if ($value['body'] === '') {
        $values[$delta]['body'] = NULL;
      }
      if (empty($value['media'])) {
        $values[$delta]['media'] = NULL;
      }
      if ($value['link_title'] === '') {
        $values[$delta]['link_title'] = NULL;
      }
      if ($value['link_uri'] === '') {
        $values[$delta]['link_uri'] = NULL;
      }
      if ($value['options'] === '') {
        $values[$delta]['options'] = NULL;
      }
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
