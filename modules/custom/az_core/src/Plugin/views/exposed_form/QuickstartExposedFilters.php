<?php

namespace Drupal\az_core\Plugin\views\exposed_form;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "az_bef",
 *   title = @Translation("Quickstart exposed filters style"),
 *   help = @Translation("Better exposed filters with additional Quickstart styles.")
 * )
 */
class QuickstartExposedFilters extends BetterExposedFilters
{

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state)
  {
    parent::exposedFormAlter($form, $form_state);

    // Attach Quickstart styles.
    $form['#attached']['library'][] = 'az_core/az-bef-sidebar';
    // Vertical style intended for sidebar use.
    $form['#attributes']['class'][] = 'az-bef-vertical';
  }
}
