<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Single on/off widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "az_exposed_filters_single",
 *   label = @Translation("Single On/Off Checkbox"),
 * )
 */
class Single extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    // Sanity check to ensure we have a filter to work with.
    if (!isset($filter)) {
      return $is_applicable;
    }

    if (is_a($filter, 'Drupal\views\Plugin\views\filter\BooleanOperator') || ($filter->isAGroup() && count($filter->options['group_info']['group_items']) == 1)) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    // Form element is designated by the element ID which is user-
    // configurable, and stored differently for grouped filters.
    $exposed_id = $filter->options['expose']['identifier'];
    $field_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $form_state);

    if (!empty($form[$field_id])) {
      // Views populates missing values in $form_state['input'] with the
      // defaults and a checkbox does not appear in $_GET (or $_POST) so it
      // will appear to be missing when a user submits a form. Because of
      // this, instead of unchecking the checkbox value will revert to the
      // default. More, the default value for select values (i.e. 'Any') is
      // reused which results in the checkbox always checked.
      $input = $form_state->getUserInput();
      // The input value ID is not always consistent.
      // Prioritize the field ID, but default to exposed ID.
      // @todo Remove $exposed_id once
      //   https://www.drupal.org/project/drupal/issues/288429 is fixed.
      $input_value = isset($input[$field_id]) ? $input[$field_id] : (isset($input[$exposed_id]) ? $input[$exposed_id] : NULL);
      $checked = FALSE;
      // We need to be super careful when working with raw input values. Let's
      // make sure the value exists in our list of possible options.
      if (in_array($input_value, array_keys($form[$field_id]['#options'])) && $input_value !== 'All') {
        $checked = (bool) $input_value;
      }
      $form[$field_id]['#type'] = 'checkbox';
      $form[$field_id]['#default_value'] = 0;
      $form[$field_id]['#return_value'] = 1;
      $form[$field_id]['#value'] = $checked ? 1 : 0;
    }
  }

}
