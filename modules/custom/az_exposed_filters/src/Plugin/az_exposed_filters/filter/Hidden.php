<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "az_exposed_filters_hidden",
 *   label = @Translation("Hidden"),
 * )
 */
class Hidden extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    $field_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $form_state);

    if (empty($form[$field_id]['#multiple'])) {
      // Single entry filters can simply be changed to a different element
      // type.
      $form[$field_id]['#type'] = 'hidden';
    }
    else {
      // Hide the label.
      $form['#info']["filter-$field_id"]['label'] = '';
      $form[$field_id]['#title'] = '';

      // Use AZ Exposed Filter's preprocess and template to output the hidden elements.
      $form[$field_id]['#theme'] = 'az_exposed_filters_hidden';
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable($filter = NULL, array $filter_options = []) {
    $is_applicable = parent::isApplicable($filter, $filter_options);

    if ((is_a($filter, 'Drupal\views\Plugin\views\filter\Date') || !empty($filter->date_handler)) && !$filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

}
