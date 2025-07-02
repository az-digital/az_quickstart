<?php

/**
 * @file
 * API documentation for Automatic Entity Label module.
 */

/**
 * Implements hook_entity_type_alter().
 *
 * These examples shows how to alter any existing entity type to provide
 * auto_entitylabel support.
 */
function hook_entity_type_alter(array &$entity_types) {
  // Enable auto_entitylabel for a single custom entity type named "MYTYPE".
  foreach ($entity_types as $entity_type) {
    if ($entity_type->getBundleOf() == 'MYTYPE') {
      $entity_type->setLinkTemplate('auto-label', $entity_type->getLinkTemplate('edit-form') . "/auto-label");
    }
  }

  // Enable auto_entitylabel for a module that provides many entity types.
  // This example specifically shows how to enable support for entity types
  // provided by the eck module.
  foreach ($entity_types as $entity_type) {
    if ($entity_type->getProvider() == 'eck' && !is_null($entity_type->getBundleOf())) {
      $entity_type->setLinkTemplate('auto-label', $entity_type->getLinkTemplate('edit-form') . "/auto-label");
    }
  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Provide an entity type's tokens to the auto_entitylabel settings form.
 */
function hook_form_auto_entitylabel_settings_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (\Drupal::routeMatch()->getRawParameters()->has('MYTYPE')) {
    $form['auto_entitylabel']['token_help']['#token_types'][] = 'MYTYPE';
  }
}

/**
 * Provide post-processing of auto generated titles (labels).
 *
 * @param string $label
 *   The auto-generated label to be altered.
 * @param object $entity
 *   The entity that the label is from.
 *
 * @see \Drupal\auto_entitylabel\AutoEntityLabelManager::generateLabel()
 */
function hook_auto_entitylabel_label_alter(&$label, $entity) {
  // Trim the label.
  $label = trim($label);
}
