<?php

/**
 * @file
 * Hooks for the field_group module.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Pre render the build of the field group element.
 *
 * @param array $element
 *   Group being rendered.
 * @param object $group
 *   The Field group info.
 * @param object $rendering_object
 *   The entity / form being rendered.
 */
function hook_field_group_pre_render(array &$element, &$group, &$rendering_object) {
  // Add all field_group format types to the js settings.
  $element['#attached']['drupalSettings']['field_group'] = [
    $group->format_type => [
      'mode' => $group->mode,
      'context' => $group->context,
      'settings' => $group->format_settings,
    ],
  ];

  $element['#weight'] = $group->weight;

  // Call the pre render function for the format type.
  $manager = Drupal::service('plugin.manager.field_group.formatters');
  $plugin = $manager->getInstance([
    'format_type' => $group->format_type,
    'configuration' => [
      'label' => $group->label,
      'settings' => $group->format_settings,
    ],
    'group' => $group,
  ]);
  $plugin->preRender($element, $rendering_object);
}

/**
 * Alter the pre_rendered build of the field group element.
 *
 * @param array $element
 *   Group being rendered.
 * @param object $group
 *   The Field group info.
 * @param object $rendering_object
 *   The entity / form being rendered.
 */
function hook_field_group_pre_render_alter(array &$element, &$group, &$rendering_object) {
  if ($group->format_type == 'htab') {
    $element['#theme_wrappers'] = [
      'container' => [
        '#attributes' => ['class' => 'foobar'],
      ],
    ];
  }
}

/**
 * Alter the pre_rendered build of the entity view.
 *
 * @param array $element
 *   Group being rendered.
 */
function hook_field_group_build_pre_render_alter(array &$element) {
  $element['#fieldgroups']['my_group']['region'] = 'new_region';
}

/**
 * Process the field group.
 *
 * @param array $element
 *   The element being processed.
 * @param object $group
 *   The group info.
 * @param object $complete_form
 *   The complete form.
 */
function hook_field_group_form_process(array &$element, &$group, &$complete_form) {
  $element['#states'] = [
    'visible' => [
      ':input[name="field_are_you_ok"]' => ['value' => 'yes'],
    ],
  ];
}

/**
 * Alter the processed build of the group.
 *
 * @param array $element
 *   The element being processed.
 * @param object $group
 *   The group info.
 * @param object $complete_form
 *   The complete form.
 */
function hook_field_group_form_process_alter(array &$element, &$group, &$complete_form) {
  $element['#states'] = [
    'visible' => [
      ':input[name="field_are_you_ok"]' => ['value' => 'yes'],
    ],
  ];
}

/**
 * Alter the form after all groups are processed.
 *
 * @param array $element
 *   The element being processed.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The form state.
 * @param object $complete_form
 *   The complete form.
 */
function hook_field_group_form_process_build_alter(array &$element, FormStateInterface $form_state, &$complete_form) {
  $element['group_example']['#states'] = [
    'visible' => [
      ':input[name="field_are_you_ok"]' => ['value' => 'yes'],
    ],
  ];
}

/**
 * Hook into the deletion event of a fieldgroup.
 *
 * @param object $group
 *   The deleted group.
 */
function hook_field_group_delete_field_group($group) {
  // Extra cleanup code.
}

/**
 * @} End of "addtogroup hooks".
 */
