<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\pager;

use Drupal\Core\Form\FormStateInterface;

/**
 * Radio Buttons pager widget implementation.
 *
 * @BetterExposedFiltersPagerWidget(
 *   id = "bef_links",
 *   label = @Translation("Links"),
 * )
 */
class Links extends PagerWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    parent::exposedFormAlter($form, $form_state);

    if (!empty($form['items_per_page'] && count($form['items_per_page']['#options']) > 1)) {
      $form['items_per_page']['#theme'] = 'bef_links';
      $form['items_per_page']['#items_per_page'] = max($form['items_per_page']['#default_value'], key($form['items_per_page']['#options']));

      // Exposed form displayed as blocks can appear on pages other than
      // the view results appear on. This can cause problems with
      // select_as_links options as they will use the wrong path. We
      // provide a hint for theme functions to correct this.
      $form['items_per_page']['#bef_path'] = $this->getExposedFormActionUrl($form_state);
    }
  }

}
