<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons sort widget implementation.
 *
 * @AzExposedFiltersSortWidget(
 *   id = "az_exposed_filters",
 *   label = @Translation("Radio Buttons"),
 * )
 */
class RadioButtons extends SortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    foreach ($this->sortElements as $element) {
      if (!empty($form[$element])) {
        $form[$element]['#theme'] = 'az_exposed_filters_radios';
        $form[$element]['#type'] = 'radios';
      }
    }
  }

}
