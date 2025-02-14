<?php

namespace Drupal\az_publication\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\inline_entity_form\TranslationHelper;
use Drupal\Core\Render\Element;

/**
 * Complex inline widget.
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
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Get inline entity form state from parent.
    $entities = $form_state->get([
      'inline_entity_form', $this->getIefId(),
      'entities',
    ]);
    $entities_count = count($entities);
    $element['entities']['#table_fields']['role'] = [
      'type' => 'callback',
      'callback' => 'az_publication_inline_entity_label_callback',
      'label' => $this->t('Role'),
      'weight' => 2,
    ];
    // Loop through values.
    foreach ($entities as $key => $value) {
      // Check if we're not rendering the form.
      if (empty($value['form'])) {
        $row = &$element['entities'][$key];
        $row['role'] = [
          '#type' => 'select',
          '#title' => $this->t('Role'),
          '#title_display' => 'invisible',
          '#default_value' => $value['role'],
          // @todo Formalize.
          '#options' => [
            'author' => $this->t('Author'),
            'chair' => $this->t('Chair'),
            'compiler' => $this->t('Compiler'),
            'collection-editor' => $this->t('Collection Editor'),
            'composer' => $this->t('Composer'),
            'container-author' => $this->t('Container Author'),
            'curator' => $this->t('Curator'),
            'director' => $this->t('Director'),
            'editor' => $this->t('Editor'),
            'translator' => $this->t('Translator'),
          ],
        ];
      }
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
    // @todo fixme.
    \Drupal::logger('Log widget values')->notice(print_r($values, TRUE));
    foreach ($values as &$value) {
      // @todo fixme.
      $value['role'] = $value['role'] ?? 'author';
    }
    $items->setValue($values);
  }

}
