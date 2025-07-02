<?php

namespace Drupal\inline_entity_form\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\TranslationHelper;
use Drupal\rat\v1\RenderArray;

/**
 * Simple inline widget.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_simple",
 *   label = @Translation("Inline entity form - Simple"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   },
 *   multiple_values = false
 * )
 */
class InlineEntityFormSimple extends InlineEntityFormBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // Trick inline_entity_form_form_alter() into attaching the handlers,
    // WidgetSubmit will be needed once extractFormValues fills the $form_state.
    $parents = array_merge($element['#field_parents'], [$items->getName()]);
    $ief_id = $this->makeIefId($parents);
    $form_state->set(['inline_entity_form', $ief_id], []);

    $element = [
      '#type' => $this->getSetting('collapsible') ? 'details' : 'fieldset',
      '#field_title' => $this->fieldDefinition->getLabel(),
      '#after_build' => [
        [get_class($this), 'removeTranslatabilityClue'],
      ],
    ] + $element;
    if ($element['#type'] == 'details') {
      // If there's user input, keep the details open. Otherwise, use settings.
      $element['#open'] = $form_state->getUserInput() ?: !$this->getSetting('collapsed');
    }

    $item = $items->get($delta);
    if ($item->target_id && !$item->entity) {
      $element['warning']['#markup'] = $this->t('Unable to load the referenced entity.');
      return $element;
    }
    $entity = $item->entity;
    $op = $entity ? 'edit' : 'add';
    $langcode = $items->getEntity()->language()->getId();
    $parents = array_merge($element['#field_parents'], [
      $items->getName(),
      $delta,
      'inline_entity_form',
    ]);
    $bundle = $this->getBundle();
    $element['inline_entity_form'] = $this->getInlineEntityForm($op, $bundle, $langcode, $delta, $parents, $entity);

    if ($op == 'edit') {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      if (!$entity->access('update')) {
        // The user isn't allowed to edit the entity, but still needs to see
        // it, to be able to reorder values.
        $element['entity_label'] = [
          '#type' => 'markup',
          '#markup' => $entity->label(),
        ];
        // Hide the inline form. getInlineEntityForm() still needed to be
        // called because otherwise the field re-ordering doesn't work.
        // Safely restrict access. Entity cacheability already set.
        RenderArray::alter($element['inline_entity_form'])->restrictAccess(FALSE, NULL);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);

    // If we're using unlimited cardinality we don't display one empty item.
    // Form validation will kick in if left empty which essentially means
    // people won't be able to submit without creating another entity.
    if (!$form_state->isSubmitted() && $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $element['#max_delta'] > 0) {
      $max = $element['#max_delta'];
      unset($element[$max]);
      $element['#max_delta'] = $max - 1;
      $items->removeItem($max);
      // Decrement the items count.
      $field_name = $element['#field_name'];
      $parents = $element[0]['#field_parents'];
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      $field_state['items_count']--;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }

    // Remove add options if the user cannot add new entities.
    if (!$this->canAddNew()) {
      if (isset($element['add_more'])) {
        unset($element['add_more']);
      }
      foreach (Element::children($element) as $delta) {
        if (isset($element[$delta]['inline_entity_form'])) {
          if ($element[$delta]['inline_entity_form']['#op'] == 'add') {
            unset($element[$delta]);
          }
        }
      }
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }

    $field_name = $this->fieldDefinition->getName();
    $parents = array_merge($form['#parents'], [$field_name]);
    $submitted_values = $form_state->getValue($parents);
    $values = [];
    foreach ($items as $delta => $value) {
      if ($element = NestedArray::getValue(
        $form,
        [$field_name, 'widget', $delta]
      )) {
        /** @var \Drupal\Core\Entity\EntityInterface $entity */
        $entity = $element['inline_entity_form']['#entity'];
        $weight = $submitted_values[$delta]['_weight'] ?? 0;
        $values[$weight] = ['entity' => $entity];
      }
    }

    // Sort items base on weights.
    ksort($values);
    $values = array_values($values);

    // Let the widget massage the submitted values.
    $values = $this->massageFormValues($values, $form, $form_state);

    // Assign the values and remove the empty ones.
    $items->setValue($values);
    $items->filterEmptyItems();

    // Populate the IEF form state with $items so that WidgetSubmit can
    // perform the necessary saves.
    $ief_id = $this->makeIefId($parents);
    $widget_state = [
      'instance' => $this->fieldDefinition,
      'delete' => [],
      'entities' => [],
    ];
    foreach ($items as $delta => $value) {
      TranslationHelper::updateEntityLangcode($value->entity, $form_state);
      $widget_state['entities'][$delta] = [
        'entity' => $value->entity,
        'needs_save' => TRUE,
      ];
    }
    $form_state->set(['inline_entity_form', $ief_id], $widget_state);

    // Put delta mapping in $form_state, so that flagErrors() can use it.
    $field_name = $this->fieldDefinition->getName();
    $field_state = WidgetBase::getWidgetState($form['#parents'], $field_name, $form_state);
    foreach ($items as $delta => $item) {
      $field_state['original_deltas'][$delta] = $item->_original_delta ?? $delta;
      unset($item->_original_delta, $item->weight);
    }
    WidgetBase::setWidgetState($form['#parents'], $field_name, $form_state, $field_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $handler_settings = $field_definition->getSettings()['handler_settings'];
    $target_entity_type_id = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    $target_entity_type = \Drupal::entityTypeManager()->getDefinition($target_entity_type_id);
    // The target entity type doesn't use bundles, no need to validate them.
    if (!$target_entity_type->getKey('bundle')) {
      return TRUE;
    }

    if (empty($handler_settings['target_bundles'])) {
      return FALSE;
    }

    if (count($handler_settings['target_bundles']) != 1) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the bundle for the inline entity.
   *
   * @return string|null
   *   The bundle, or NULL if not known.
   */
  protected function getBundle() {
    if (!empty($this->getFieldSetting('handler_settings')['target_bundles'])) {
      return reset($this->getFieldSetting('handler_settings')['target_bundles']);
    }
  }

}
