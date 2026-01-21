<?php

namespace Drupal\az_publication\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldFilteredMarkup;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\inline_entity_form\TranslationHelper;
use Drupal\Core\Render\Element;

/**
 * Complex inline widget for references with assigned role.
 *
 * @FieldWidget(
 *   id = "az_entity_role_inline_entity_form_complex",
 *   label = @Translation("Entity Role Inline entity form - Complex"),
 *   field_types = {
 *     "az_entity_role_reference"
 *   },
 *   multiple_values = true
 * )
 */
class AZEntityRoleInlineFormComplex extends InlineEntityFormComplex {

  /**
   * The array of options for the widget.
   */
  protected array $roleOptions;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Get inline entity form state from parent.
    $entities = $form_state->get([
      'inline_entity_form', $this->getIefId(),
      'entities',
    ]);
    // Render as a table that understands how to output the role element.
    $element['entities']['#theme'] = 'az_inline_entity_role_form_entity_table';
    // Loop through values.
    foreach ($entities as $key => $value) {
      // Add a form element for the role.
      $row = &$element['entities'][$key];
      $row['role'] = [
        '#type' => 'select',
        '#title' => $this->t('Role'),
        '#title_display' => 'invisible',
        '#default_value' => $value['role'] ?? NULL,
        // @todo Formalize as part of field type?
        '#options' => $this->getOptions($items->getEntity()),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function updateRowWeights($element, FormStateInterface $form_state, $form) {
    $ief_id = $element['#ief_id'];

    // Loop over the submitted delta values and update the weight of the
    // entities in the form state.
    foreach (Element::children($element['entities']) as $key) {
      $form_state->set(
        ['inline_entity_form', $ief_id, 'entities', $key, 'weight'],
        $element['entities'][$key]['delta']['#value']);
      // Update the role also, not just the weight.
      $form_state->set(
        ['inline_entity_form', $ief_id, 'entities', $key, 'role'],
        $element['entities'][$key]['role']['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareFormState(FormStateInterface $form_state, FieldItemListInterface $items, $translating = FALSE) {
    $widget_state = $form_state->get(['inline_entity_form', $this->iefId]);
    if (empty($widget_state)) {
      $widget_state = [
        'instance' => $this->fieldDefinition,
        'form' => NULL,
        'delete' => [],
        'entities' => [],
      ];
      // Store the $items entities in the widget state, for further
      // manipulation.
      foreach ($items as $delta => $item) {
        // Display the entity in the correct translation.
        $entity = $item->entity;
        $role = $item->role ?? 'author';
        if ($translating) {
          $entity = TranslationHelper::prepareEntity($entity, $form_state);
        }
        $widget_state['entities'][$delta] = [
          'entity' => $entity,
          'weight' => $delta,
          'role' => $role,
          'form' => NULL,
          'needs_save' => $entity->isNew(),
        ];
      }
      $form_state->set(['inline_entity_form', $this->iefId], $widget_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    parent::extractFormValues($items, $form, $form_state);
    $values = $items->getValue();
    foreach ($values as &$value) {
      $value['role'] = $value['role'] ?? 'author';
    }
    $items->setValue($values);
  }

  /**
   * Returns the array of options for the widget.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity for which to return options.
   *
   * @return array
   *   The array of options for the widget.
   */
  protected function getOptions(FieldableEntityInterface $entity) {
    if (!isset($this->roleOptions)) {
      // Limit the settable options for the current user account.
      $options = $this->fieldDefinition
        ->getFieldStorageDefinition()
        ->getOptionsProvider('role', $entity)
        ->getSettableOptions(\Drupal::currentUser());

      array_walk_recursive($options, [$this, 'sanitizeLabel']);

      $this->roleOptions = $options;
    }
    return $this->roleOptions;
  }

  /**
   * Sanitizes a string label to display as an option.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $label
   *   The label to sanitize.
   */
  protected function sanitizeLabel(&$label) {
    // Allow a limited set of HTML tags.
    $label = FieldFilteredMarkup::create($label);
  }

}
