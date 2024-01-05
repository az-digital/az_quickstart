<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\pager;

use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons pager widget implementation.
 *
 * @AzExposedFiltersPagerWidget(
 *   id = "az_exposed_filters",
 *   label = @Translation("Radio Buttons"),
 * )
 */
class RadioButtons extends PagerWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    if (!empty($form['items_per_page'])) {
      $form['items_per_page']['#type'] = 'radios';
      $form['items_per_page']['#prefix'] = '<div class="az-exposed-filters-sortby az-exposed-filters-select-as-radios">';
      $form['items_per_page']['#suffix'] = '</div>';
    }
  }

}
