<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetBase;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetInterface;

/**
 * Base class for Better exposed pager widget plugins.
 */
abstract class SortWidgetBase extends BetterExposedFiltersWidgetBase implements BetterExposedFiltersWidgetInterface {

  use StringTranslationTrait;

  /**
   * List of available exposed sort form element keys.
   *
   * @var array
   */
  protected array $sortElements = [
    'sort_bef_combine',
    'sort_by',
    'sort_order',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'advanced' => [
        'collapsible' => FALSE,
        'collapsible_label' => $this->t('Sort options'),
        'combine' => FALSE,
        'combine_rewrite' => '',
        'is_secondary' => FALSE,
        'reset' => FALSE,
        'reset_label' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $handler = NULL, array $options = []): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = [];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced sort options'),
    ];

    // We can only combine sort order and sort by if both options are exposed.
    $form['advanced']['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine sort order with sort by'),
      '#default_value' => !empty($this->configuration['advanced']['combine']),
      '#description' => $this->t('Combines the sort by options and order (ascending or descending) into a single list.  Use this to display "Option1 (ascending)", "Option1 (descending)", "Option2 (ascending)", "Option2 (descending)" in a single form element. Sort order should first be exposed by selecting <em>Allow people to choose the sort order</em>.'),
      '#states' => [
        'enabled' => [
          ':input[name="exposed_form_options[expose_sort_order]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['combine_rewrite'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Rewrite the text displayed'),
      '#default_value' => $this->configuration['advanced']['combine_rewrite'],
      '#description' => $this->t('Use this field to rewrite the text displayed for combined sort options and sort order. Use the format of current_text|replacement_text, one replacement per line. For example: <pre>
Post date Asc|Oldest first
Post date Desc|Newest first
Title Asc|A -> Z
Title Desc|Z -> A</pre> Leave the replacement text blank to remove an option altogether. The order the options appear will be changed to match the order of options in this field.'),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][sort][configuration][advanced][combine]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['reset'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include a "Reset sort" option'),
      '#default_value' => !empty($this->configuration['advanced']['reset']),
      '#description' => $this->t('Adds a "Reset sort" link; Views will use the default sort order.'),
    ];

    $form['advanced']['reset_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"Reset sort" label'),
      '#default_value' => $this->configuration['advanced']['reset_label'],
      '#description' => $this->t('This cannot be left blank if the above option is checked'),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][sort][configuration][advanced][reset]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="exposed_form_options[bef][sort][configuration][advanced][reset]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make sort options collapsible'),
      '#default_value' => !empty($this->configuration['advanced']['collapsible']),
      '#description' => $this->t(
        'Puts the sort options in a collapsible details element.'
      ),
    ];

    $form['advanced']['collapsible_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Collapsible details element title'),
      '#default_value' => $this->configuration['advanced']['collapsible_label'],
      '#description' => $this->t('This cannot be left blank or there will be no way to show/hide sort options.'),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][sort][configuration][advanced][collapsible]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="exposed_form_options[bef][sort][configuration][advanced][collapsible]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['is_secondary'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('This is a secondary option'),
      '#default_value' => !empty($this->configuration['advanced']['is_secondary']),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][general][allow_secondary]"]' => ['checked' => TRUE],
        ],
      ],
      '#description' => $this->t('Places this element in the secondary options portion of the exposed form.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state): void {
    $is_collapsible = $this->configuration['advanced']['collapsible']
      && !empty($this->configuration['advanced']['collapsible_label']);
    $is_secondary = !empty($form['secondary']) && $this->configuration['advanced']['is_secondary'];

    // Check for combined sort_by and sort_order.
    if ($this->configuration['advanced']['combine'] && !empty($form['sort_order'])) {
      $options = [];
      $selected = '';

      foreach ($form['sort_by']['#options'] as $by_key => $by_val) {
        foreach ($form['sort_order']['#options'] as $order_key => $order_val) {
          // Use a space to separate the two keys, we'll unpack them in our
          // submit handler.
          $options[$by_key . '_' . $order_key] = "$by_val $order_val";

          if ($form['sort_order']['#default_value'] === $order_key && empty($selected)) {
            // Respect default sort order set in Views. The default sort field
            // will be the first one if there are multiple sort criteria.
            $selected = $by_key . '_' . $order_key;
          }
        }
      }

      // Rewrite the option values if any were specified.
      if (!empty($this->configuration['advanced']['combine_rewrite'])) {
        $options = BetterExposedFiltersHelper::rewriteOptions($options, $this->configuration['advanced']['combine_rewrite'], TRUE);
        if (!isset($options[$selected])) {
          // Avoid "illegal choice" errors if the selected option is
          // eliminated by the rewrite.
          $selected = NULL;
        }
      }

      // Add reset sort option at the top of the list.
      if ($this->configuration['advanced']['reset']) {
        $options = [' ' => $this->configuration['advanced']['reset_label']] + $options;
      }

      $form['sort_bef_combine'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $selected,
        // Already sanitized by Views.
        '#title' => $form['sort_by']['#title'],
      ];

      // Add our submit routine to process.
      $form['#submit'][] = [$this, 'sortCombineSubmitForm'];

      // Pretend we're another exposed form widget.
      $form['#info']['sort-sort_bef_combine'] = [
        'value' => 'sort_bef_combine',
      ];

      // Remove the existing sort_by and sort_order elements.
      unset($form['sort_by']);
      unset($form['sort_order']);
    }
    else {
      // Add reset sort option if selected.
      if ($this->configuration['advanced']['reset']) {
        array_unshift($form['sort_by']['#options'], $this->configuration['advanced']['reset_label']);
      }
    }

    // If selected, collect all sort-related form elements and put them in a
    // details' element.
    if ($is_collapsible) {
      $form['bef_sort_options'] = [
        '#type' => 'details',
        '#title' => $this->configuration['advanced']['collapsible_label'],
      ];

      if ($is_secondary) {
        // Move secondary elements.
        $form['bef_sort_options']['#group'] = 'secondary';
      }
    }

    // Iterate over all exposed sort elements.
    foreach ($this->sortElements as $element) {
      // Sanity check to make sure the element exists.
      if (empty($form[$element])) {
        continue;
      }

      // Move collapsible elements.
      if ($is_collapsible) {
        $this->addElementToGroup($form, $form_state, $element, 'bef_sort_options');
      }
      // Move secondary elements.
      elseif ($is_secondary) {
        $this->addElementToGroup($form, $form_state, $element, 'secondary');
      }

      // Finally, add some metadata to the form element.
      $this->addContext($form[$element]);
    }
  }

  /**
   * Unpacks sort_by and sort_order from the sort_bef_combine element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function sortCombineSubmitForm(array $form, FormStateInterface $form_state): void {
    $sort_by = $sort_order = '';
    $combined = $form_state->getValue('sort_bef_combine');
    if (!empty($combined)) {
      $parts = explode('_', $combined);
      $sort_order = trim(array_pop($parts));
      $sort_by = trim(implode('_', $parts));
    }
    $form_state->setValue('sort_by', $sort_by);
    $form_state->setValue('sort_order', $sort_order);
  }

}
