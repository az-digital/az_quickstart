<?php

namespace Drupal\inline_entity_form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form\Element\InlineEntityForm;

/**
 * Performs widget submission.
 *
 * Widgets don't save changed entities, nor do they delete removed entities.
 * Instead, they flag them so that changes are only applied when the main form
 * is submitted.
 */
class WidgetSubmit {

  /**
   * Attaches the widget submit functionality to the given form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function attach(array &$form, FormStateInterface $form_state) {
    // $form['#ief_element_submit'] runs after the #ief_element_submit
    // callbacks of all subelements, which means that doSubmit() has
    // access to the final IEF $form_state.
    $form['#ief_element_submit'][] = [get_called_class(), 'doSubmit'];
  }

  /**
   * Submits the widget elements, saving and deleted entities where needed.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function doSubmit(array $form, FormStateInterface $form_state) {
    $referenceUpgrader = new ReferenceUpgrader();
    $widget_states =& $form_state->get('inline_entity_form');
    // Widget states can be in an arbitrary order depending on user's form
    // interaction. We sort them lexicographically in reverse order to get inner
    // forms before outer forms, to ensure inside-out saving of entities.
    // @see \Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormBase::makeIefId
    $widget_states = $widget_states ?? [];
    krsort($widget_states, SORT_STRING);
    foreach ($widget_states as &$widget_state) {
      $widget_state += ['entities' => [], 'delete' => []];
      foreach ($widget_state['entities'] as &$entity_item) {
        if (!empty($entity_item['entity']) && !empty($entity_item['needs_save'])) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $entity_item['entity'];
          $handler = InlineEntityForm::getInlineFormHandler($entity->getEntityTypeId());
          $referenceUpgrader->upgradeEntityReferences($entity);
          $handler->save($entity);
          $referenceUpgrader->registerEntity($entity);
          $entity_item['needs_save'] = FALSE;
        }
      }

      /** @var \Drupal\Core\Entity\ContentEntityInterface $entities */
      foreach ($widget_state['delete'] as $entity) {
        $entity->delete();
      }
      unset($widget_state['delete']);
    }
  }

}
