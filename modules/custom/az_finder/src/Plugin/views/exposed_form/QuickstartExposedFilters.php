<?php

declare(strict_types = 1);

namespace Drupal\az_finder\Plugin\views\exposed_form;

use Drupal\better_exposed_filters\Plugin\views\exposed_form\BetterExposedFilters;
use Drupal\Core\Form\FormStateInterface;

/**
 * Exposed form plugin that provides a basic exposed form.
 *
 * @ingroup views_exposed_form_plugins
 *
 * @ViewsExposedForm(
 *   id = "az_bef",
 *   title = @Translation("Quickstart Exposed Filters"),
 *   help = @Translation("Better exposed filters with additional Quickstart styles.")
 * )
 */
class QuickstartExposedFilters extends BetterExposedFilters {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    // Mark form as QuickstartExposedFilters form for easier alterations.
    $form['#context']['az_bef'] = TRUE;
    // Attach Quickstart styles.
    $form['#attached']['library'][] = 'az_finder/filter';
    // Attach JavaScript settings for the minimum search input length.
    $form['#attached']['drupalSettings']['azFinder']['minSearchLength'] = $this->options['az_bef']['finder']['min_search_length'] ?? 1;
    // Vertical style intended for sidebar use.
    $form['#attributes']['class'][] = 'az-bef-vertical';
    // Create the clear all filters button.
    $count = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => [
          'js-finder-filter-count',
          'ml-1',
        ],
      ],
    ];

    $form['clear_all_filters'] = [
      '#type' => 'html_tag',
      '#tag' => 'button',
      '#value' => $this->t('Clear all filters'),
      'count' => $count,
      '#attributes' => [
        'class' => [
          'btn',
          'btn-sm',
          'btn-primary',
          'btn-block',
          'js-finder-clear-all',
          'd-none',
          'mx-1',
          'mb-3',
        ],
      ],
      '#weight' => -10,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {

    // Add a field for setting the minimum search input length.
    $form['az_bef']['finder']['min_search_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum Search Input Length'),
      '#description' => $this->t('The minimum number of characters required in the search field to count as an active filter.'),
      '#default_value' => $this->options['az_bef']['finder']['min_search_length'] ?? 1,
      '#min' => 0,
      '#step' => 1,
    ];
    parent::buildOptionsForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    // Save the minimum search input length setting.
    $this->options['az_bef']['finder']['min_search_length'] = $form_state->getValue([
      'az_bef',
      'finder',
      'min_search_length',
    ]);
  }

}
