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
class QuickstartExposedFilters extends BetterExposedFilters {

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(&$form, FormStateInterface $form_state) {
    parent::exposedFormAlter($form, $form_state);

    // Attach Quickstart styles.
    $form['#attached']['library'][] = 'az_core/az-bef-sidebar';
    // Vertical style intended for sidebar use.
    $form['#attributes']['class'][] = 'az-bef-vertical';
    // Form checkboxes.
    $form['colour_select'] = [
      '#type' => 'radios',
      '#title' => $this->t('Pick a colour'),
      '#options' => [
        'blue' => $this->t('Blue'),
        'white' => $this->t('White'),
        'black' => $this->t('Black'),
        'other' => $this->t('Other'),
      ],
      // We cannot give id attribute to radio buttons as it will break their functionality, making them inaccessible.
      /* '#attributes' => [
        // Define a static id so we can easier select it.
        'id' => 'field_colour_select',
      ],*/
    ];

    // This textfield will only be shown when the option 'Other'
    // is selected from the radios above.
    $form['custom_colour'] = [
      '#type' => 'textfield',
      '#size' => '60',
      '#placeholder' => 'Enter favourite colour',
      '#attributes' => [
        'id' => 'custom-colour',
      ],
      '#states' => [
        // Show this textfield only if the radio 'other' is selected above.
        'visible' => [
          // Don't mistake :input for the type of field or for a css selector --
          // it's a jQuery selector. 
          // You can always use :input or any other jQuery selector here, no matter 
          // whether your source is a select, radio or checkbox element.
          // in case of radio buttons we can select them by thier name instead of id.
          ':input[name="colour_select"]' => ['value' => 'other'],
        ],
      ],
    ];

    // Create the submit button.
    $form['submit'] = [
      '#type' => 'inline_template',
      '#template' => '<button type="button" class="btn btn-success" disabled="disabled">Product unavailable</button>',
    ];
  }

}
