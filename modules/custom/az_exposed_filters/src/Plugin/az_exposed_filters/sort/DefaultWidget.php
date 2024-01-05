<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @AzExposedFiltersSortWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends SortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    foreach ($this->sortElements as $element) {
      if (!empty($form[$element])) {
        $form[$element]['#type'] = 'select';
      }
    }
  }

}
