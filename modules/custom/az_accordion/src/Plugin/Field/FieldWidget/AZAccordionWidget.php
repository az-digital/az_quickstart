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
class AZAccordionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    /** @var \Drupal\az_accordion\Plugin\Field\FieldType\AZAccordionItem $item */
    $item = $items[$delta];

    // Get current collapse status.
    $field_name = $this->fieldDefinition->getName();
    $field_parents = $element['#field_parents'];
    $widget_state = static::getWidgetState($field_parents, $field_name, $form_state);
    $wrapper = $widget_state['ajax_wrapper_id'];
    $status = (isset($widget_state['open_status'][$delta])) ? $widget_state['open_status'][$delta] : FALSE;

    // We may have had a deleted row. This shouldn't be necessary to check, but
    // The experimental paragraphs widget extracts values before the submit
    // handler.
    if (isset($widget_state['original_deltas'][$delta]) && ($widget_state['original_deltas'][$delta] !== $delta)) {
      $delta = $widget_state['original_deltas'][$delta];
    }

    // New field values shouldn't be considered collapsed.
    if ($item->isEmpty()) {
      $status = TRUE;
    }

    // Generate a preview if we need one.
    if (!$status) {

      // Bootstrap wrapper.
      $element['preview_container'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' =>
            ['col-12', 'accordion-preview'],
        ],
      ];

      // Accordion item.
      $element['preview_container']['accordion_preview'] = [
        '#theme' => 'az_accordion',
        '#title' => $item->title ?? '',
        '#body' => check_markup(
          $item->title),
        '#attributes' => ['class' => ['card']],
      ];

      // Add accordion class from options.
      if (!empty($item->options['class'])) {
        $element['preview_container']['accordion_preview']['#attributes']['class'][] = $item->options['class'];
      }
    }

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

    if (!$item->isEmpty()) {
      $button_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'toggle']
      ));
      $remove_name = implode('-', array_merge($field_parents,
        [$field_name, $delta, 'remove']
      ));
      $element['toggle'] = [
        '#type' => 'submit',
        '#limit_validation_errors' => [],
        '#attributes' => ['class' => ['button--extrasmall', 'ml-3']],
        '#submit' => [[$this, 'accordionSubmit']],
        '#value' => ($status ? $this->t('Collapse Accordion') : $this->t('Edit Accordion')),
        '#name' => $button_name,
        '#ajax' => [
          'callback' => [$this, 'accordionAjax'],
          'wrapper' => $wrapper,
        ],
      ];

      if (!empty($widget_state['items_count']) && ($widget_state['items_count'] > 1)) {
        $element['remove'] = [
          '#name' => $remove_name,
          '#delta' => $delta,
          '#type' => 'submit',
          '#value' => $this->t('Delete Accordion'),
          '#validate' => [],
          '#submit' => [[$this, 'accordionRemove']],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--extrasmall', 'ml-3']],
          '#ajax' => [
            'callback' => [$this, 'accordionAjax'],
            'wrapper' => $wrapper,
          ],
        ];
      }
    }

    $element['#theme_wrappers'] = ['container', 'form_element'];
    $element['#attributes']['class'][] = 'az-accordion-elements';
    $element['#attributes']['class'][] = $status ? 'az-accordion-elements-open' : 'az-accordion-elements-closed';
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

  /**
   * Submit handler for toggle button.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function accordionSubmit(array $form, FormStateInterface $form_state) {

    // Get triggering element.
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = $triggering_element['#array_parents'];
    array_pop($array_parents);

    // Determine delta.
    $delta = array_pop($array_parents);

    // Get the widget.
    $element = NestedArray::getValue($form, $array_parents);
    $field_name = $element['#field_name'];
    $field_parents = $element['#field_parents'];

    // Load current widget settings.
    $settings = static::getWidgetState($field_parents, $field_name, $form_state);

    // Prepare to toggle state.
    $status = TRUE;
    if (isset($settings['open_status'][$delta])) {
      $status = !$settings['open_status'][$delta];
    }
    $settings['open_status'][$delta] = $status;

    // Save new state and rebuild form.
    static::setWidgetState($field_parents, $field_name, $form_state, $settings);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for remove button. See multiple_fields_remove_button module.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function accordionRemove(array $form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    $formInputs = $form_state->getUserInput();
    $button = $form_state->getTriggeringElement();
    $delta = $button['#delta'];
    // Where in the form we'll find the parent element.
    $address = array_slice($button['#array_parents'], 0, -2);

    // Go one level up in the form, to the widgets container.
    $parent_element = NestedArray::getValue($form, $address);
    $field_name = $parent_element['#field_name'];
    $parents = $parent_element['#field_parents'];
    $field_state = WidgetBase::getWidgetState($parents, $field_name, $form_state);

    // Go ahead and renumber everything from our delta to the last
    // item down one. This will overwrite the item being removed.
    for ($i = $delta; $i <= $field_state['items_count']; $i++) {
      $old_element_address = array_merge($address, [$i + 1]);
      $new_element_address = array_merge($address, [$i]);

      $moving_element = NestedArray::getValue($form, $old_element_address);
      $keys = array_keys($old_element_address, 'widget', TRUE);
      foreach ($keys as $key) {
        unset($old_element_address[$key]);
      }
      $moving_element_value = NestedArray::getValue($formValues, $old_element_address);
      $moving_element_input = NestedArray::getValue($formInputs, $old_element_address);

      $keys = array_keys($new_element_address, 'widget', TRUE);
      foreach ($keys as $key) {
        unset($new_element_address[$key]);
      }
      // Tell the element where it's being moved to.
      $moving_element['#parents'] = $new_element_address;

      // Delete default value for the last deleted element.
      if ($field_state['items_count'] === 0) {
        $struct_key = NestedArray::getValue($formInputs, $new_element_address);
        if (is_null($moving_element_value)) {
          foreach ($struct_key as &$key) {
            $key = '';
          }
          $moving_element_value = $struct_key;
        }
        if (is_null($moving_element_input)) {
          $moving_element_input = $moving_element_value;
        }
      }

      // Move the element around.
      NestedArray::setValue($formValues, $moving_element['#parents'], $moving_element_value, TRUE);
      NestedArray::setValue($formInputs, $moving_element['#parents'], $moving_element_input);

      // Save new element values.
      foreach ($formValues as $key => $value) {
        $form_state->setValue($key, $value);
      }
      $form_state->setUserInput($formInputs);

      // Move the entity in our saved state.
      if (isset($field_state['original_deltas'][$i + 1])) {
        $field_state['original_deltas'][$i] = $field_state['original_deltas'][$i + 1];
      }
      else {
        unset($field_state['original_deltas'][$i]);
      }
    }

    // Replace the deleted entity with an empty one. This helps to ensure that
    // trying to add a new entity won't resurrect a deleted entity
    // from the trash bin.
    // $count = count($field_state['entity']);
    // Then remove the last item. But we must not go negative.
    if ($field_state['items_count'] > 0) {
      $field_state['items_count']--;
    }

    // Fix the weights. Field UI lets the weights be in a range of
    // (-1 * item_count) to (item_count). This means that when we remove one,
    // the range shrinks; weights outside of that range then get set to
    // the first item in the select by the browser, floating them to the top.
    // We use a brute force method because we lost weights on both ends
    // and if the user has moved things around, we have to cascade because
    // if I have items weight weights 3 and 4, and I change 4 to 3 but leave
    // the 3, the order of the two 3s now is undefined and may not match what
    // the user had selected.
    $address = array_slice($button['#array_parents'], 0, -2);
    $keys = array_keys($address, 'widget', TRUE);
    foreach ($keys as $key) {
      unset($address[$key]);
    }
    $input = NestedArray::getValue($formInputs, $address);

    if ($input && is_array($input)) {
      // Sort by weight.
      // phpcs:ignore
      uasort($input, '_field_multiple_value_form_sort_helper');

      // Reweight everything in the correct order.
      $weight = -1 * $field_state['items_count'];
      foreach ($input as $key => $item) {
        if ($item) {
          $input[$key]['_weight'] = $weight++;
        }
      }
      NestedArray::setValue($formInputs, $address, $input);
      $form_state->setUserInput($formInputs);
    }

    $element_id = $form[$field_name]['#id'] ?? '';
    if (!$element_id) {
      $element_id = $parent_element['#id'];
    }
    $field_state['wrapper_id'] = $element_id;
    WidgetBase::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback returning list widget container for ajax submit.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   Ajax response as render array.
   */
  public function accordionAjax(array &$form, FormStateInterface $form_state) {

    // Find the widget and return it.
    $element = [];
    $triggering_element = $form_state->getTriggeringElement();
    $oops = $triggering_element['#array_parents'];
    $array_parents = array_slice($triggering_element['#array_parents'], 0, -2);
    $element = NestedArray::getValue($form, $array_parents);

    return $element;
  }

}
