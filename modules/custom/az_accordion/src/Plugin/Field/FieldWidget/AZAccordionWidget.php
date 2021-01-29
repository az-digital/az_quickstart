<?php

namespace Drupal\az_accordion\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Defines the 'az_accordion' field widget.
 *
 * @FieldWidget(
 *   id = "az_accordion",
 *   label = @Translation("accordion"),
 *   field_types = {
 *     "az_accordion"
 *   }
 * )
 */
class AZaccordionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accordion Title'),
      '#default_value' => isset($items[$delta]->title) ? $items[$delta]->title : NULL,
    ];

    $element['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Accordion Item'),
      '#default_value' => isset($items[$delta]->body) ? $items[$delta]->body : NULL,
      '#format' => $items[$delta]->body_format ?? 'basic_html',
    ];

    $element['collapsed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapsed by Default'),
      '#default_value' => isset($items[$delta]->collapsed) ? $items[$delta]->collapsed : TRUE,
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
