<?php

namespace Drupal\az_accordion\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'az_accordion' field widget.
 */
#[FieldWidget(
  id: 'az_accordion',
  label: new TranslatableMarkup('accordion'),
  field_types: ['az_accordion'],
)]
class AZAccordionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accordion Item Title'),
      '#default_value' => $items[$delta]->title ?? NULL,
      '#maxlength' => 255,
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Accordion Item Body'),
      '#default_value' => $items[$delta]->body ?? NULL,
      '#format' => $items[$delta]->body_format ?? 'az_standard',
    ];

    $element['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed by Default'),
      '#default_value' => $items[$delta]->collapsed ?? TRUE,
    ];

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-accordion-elements';
    $element['#attached']['library'][] = 'az_accordion/az_accordion';

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
      $values[$delta]['body'] = $value['body']['value'];
      $values[$delta]['body_format'] = $value['body']['format'];
    }
    return $values;
  }

}
