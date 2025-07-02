<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @BetterExposedFiltersSortWidget(
 *   id = "default",
 *   label = @Translation("Default"),
 * )
 */
class DefaultWidget extends SortWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);

    foreach ($this->sortElements as $element) {
      if (!empty($form[$element])) {
        $form[$element]['#type'] = 'select';
      }
    }
  }

}
