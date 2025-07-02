<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons sort widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "bef",
 *   label = @Translation("Radio Buttons"),
 * )
 */
class RadioButtons extends SortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);

    foreach ($this->sortElements as $element) {
      if (!empty($form[$element])) {
        $form[$element]['#theme'] = 'bef_radios';
        $form[$element]['#type'] = 'radios';
      }
    }
  }

}
