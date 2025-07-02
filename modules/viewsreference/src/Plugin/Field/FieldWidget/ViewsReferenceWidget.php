<?php

namespace Drupal\viewsreference\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'entity_reference_autocomplete' widget.
 *
 * @FieldWidget(
 *   id = "viewsreference_autocomplete",
 *   label = @Translation("Views reference autocomplete"),
 *   description = @Translation("An autocomplete views reference field."),
 *   field_types = {
 *     "viewsreference"
 *   }
 * )
 */
class ViewsReferenceWidget extends EntityReferenceAutocompleteWidget {

  use ViewsReferenceTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element = $this->fieldElement($items, $delta, $element, $form, $form_state);
    $form['#validate'][] = [$this, 'elementValidate'];
    return $element;
  }

  /**
   * Validate that a display ID is selected for a View.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function elementValidate(array $element, FormStateInterface $form_state) {
    $key = $this->fieldDefinition->getName();
    $field_values = $form_state->getValue($key);
    if (is_array($field_values)) {
      self::validateDisplayId($field_values, $form_state, $key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return $element['display_id'] ?? FALSE;
  }

}
