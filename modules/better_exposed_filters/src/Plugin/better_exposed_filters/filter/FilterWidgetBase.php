<?php

namespace Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetBase;
use Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetInterface;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Base class for Better exposed filters widget plugins.
 */
abstract class FilterWidgetBase extends BetterExposedFiltersWidgetBase implements BetterExposedFiltersWidgetInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(mixed $filter = NULL, array $filter_options = []): bool {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $is_applicable = FALSE;

    // Sanity check to ensure we have a filter to work with.
    if (is_null($filter)) {
      return FALSE;
    }

    // Check various filter types and determine what options are available.
    if (is_a($filter, 'Drupal\views\Plugin\views\filter\StringFilter') || is_a($filter, 'Drupal\views\Plugin\views\filter\InOperator')) {
      if (in_array($filter->operator, ['in', 'or', 'and', 'not'])) {
        $is_applicable = TRUE;
      }
      if (in_array($filter->operator, ['empty', 'not empty'])) {
        $is_applicable = TRUE;
      }
    }

    if (is_a($filter, 'Drupal\views\Plugin\views\filter\BooleanOperator')) {
      $is_applicable = TRUE;
    }

    if (is_a($filter, 'Drupal\taxonomy\Plugin\views\filter\TaxonomyIndexTid')) {
      // Autocomplete and dropdown taxonomy filter are both instances of
      // TaxonomyIndexTid, but we can't show BEF options for the autocomplete
      // widget.
      if ($filter_options['type'] == 'select') {
        $is_applicable = TRUE;
      }
    }

    if ($filter->isAGroup()) {
      $is_applicable = TRUE;
    }

    if (is_a($filter, 'Drupal\search_api\Plugin\views\filter\SearchApiFulltext')) {
      $is_applicable = TRUE;
    }

    if (is_a($filter, 'Drupal\facets_exposed_filters\Plugin\views\filter\FacetsFilter')) {
      $is_applicable = TRUE;
    }

    return $is_applicable;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return parent::defaultConfiguration() + [
      'advanced' => [
        'collapsible' => FALSE,
        'collapsible_disable_automatic_open' => FALSE,
        'is_secondary' => FALSE,
        'placeholder_text' => '',
        'rewrite' => [
          'filter_rewrite_values' => '',
          'filter_rewrite_values_key' => FALSE,
        ],
        'sort_options' => FALSE,
        'hide_label' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $filter_widget_type = $this->getExposedFilterWidgetType();

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced filter options'),
      '#weight' => 10,
    ];

    // Allow users to sort options.
    $supported_types = ['select'];
    if (in_array($filter_widget_type, $supported_types)) {
      $form['advanced']['sort_options'] = [
        '#type' => 'checkbox',
        '#title' => 'Sort filter options',
        '#default_value' => !empty($this->configuration['advanced']['sort_options']),
        '#description' => $this->t('The options will be sorted alphabetically.'),
      ];
    }

    // Allow users to specify placeholder text.
    $supported_types = ['entity_autocomplete', 'textfield'];
    if (in_array($filter_widget_type, $supported_types)) {
      $form['advanced']['placeholder_text'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Placeholder text'),
        '#description' => $this->t('Text to be shown in the text field until it is edited. Leave blank for no placeholder to be set.'),
        '#default_value' => $this->configuration['advanced']['placeholder_text'],
      ];
    }

    // Allow rewriting of filter options for any filter. String and numeric
    // filters allow unlimited filter options via textfields, so we can't
    // offer rewriting for those.
    // @todo check other core filter types
    if ((!$filter instanceof StringFilter && !$filter instanceof NumericFilter) || $filter->isAGroup()) {
      $form['advanced']['rewrite']['filter_rewrite_values'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Rewrite the text displayed'),
        '#default_value' => $this->configuration['advanced']['rewrite']['filter_rewrite_values'],
        '#description' => $this->t('Use this field to rewrite the filter options displayed. Use the format of current_text|replacement_text, one replacement per line. For example: <pre>
  Current|Replacement
  On|Yes
  Off|No
  </pre> Leave the replacement text blank to remove an option altogether. If using hierarchical taxonomy filters, do not including leading hyphens in the current text.
          '),
      ];
      $form['advanced']['rewrite']['filter_rewrite_values_key'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Rewrite the text displayed based on key'),
        '#default_value' => $this->configuration['advanced']['rewrite']['filter_rewrite_values_key'],
        '#description' => $this->t('Change behavior of "Rewrite the text displayed" to overwrite labels based on option key. eg. All|New label'),
      ];
    }

    $form['advanced']['hide_label'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the label'),
      '#description' => $this->t('Hides the label visually, so it is still usable for accessibility purposes.'),
      '#default_value' => !empty($this->configuration['advanced']['hide_label']),
    ];

    // Allow any filter to be collapsible.
    $form['advanced']['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make filter options collapsible'),
      '#default_value' => !empty($this->configuration['advanced']['collapsible']),
      '#description' => $this->t(
        'Puts the filter options in a collapsible details element.'
      ),
    ];

    // Allow any filter to be collapsible.
    $form['advanced']['collapsible_disable_automatic_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable the automatic opening of collapsed filters with selections'),
      '#default_value' => !empty($this->configuration['advanced']['collapsible_disable_automatic_open']),
      '#description' => $this->t(
        'When a selection is made, by default the collapsed filter will be set to open. If you provide an alternative means for the user to see filter selections, you can the default open behavior by enabling this.'
      ),
      '#states' => [
        'visible' => [
          ':input[name="exposed_form_options[bef][filter][' . $filter->options['id'] . '][configuration][advanced][collapsible]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Allow any filter to be moved into the secondary options' element.
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
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $filter_id = $filter->options['expose']['identifier'];
    $field_id = $this->getExposedFilterFieldId();
    $is_collapsible = $this->configuration['advanced']['collapsible'];
    $collapsible_disable_automatic_open = $this->configuration['advanced']['collapsible_disable_automatic_open'];
    $is_secondary = !empty($form['secondary']) && $this->configuration['advanced']['is_secondary'];

    // Sort options alphabetically.
    if ($this->configuration['advanced']['sort_options']) {
      $form[$field_id]['#nested'] = $filter->options['hierarchy'] ?? FALSE;
      $form[$field_id]['#nested_delimiter'] = '-';
      $form[$field_id]['#pre_process'][] = [$this, 'processSortedOptions'];
    }

    // Check for placeholder text.
    if (!empty($this->configuration['advanced']['placeholder_text'])) {
      // @todo Add token replacement for placeholder text.
      $form[$field_id]['#placeholder'] = $this->configuration['advanced']['placeholder_text'];
    }

    // Visually hidden label.
    if (!empty($this->configuration['advanced']['hide_label'])) {
      // Check if the field was wrapped with a fieldset.
      // @see \Drupal\views\Plugin\views\filter\FilterPluginBase::buildExposedForm
      // @see \Drupal\views\Plugin\views\filter\FilterPluginBase::buildValueWrapper
      if (empty($form["{$field_id}_wrapper"][$field_id])) {
        $form[$field_id]['#title_display'] = 'invisible';
      }
      else {
        $form["{$field_id}_wrapper"]['#title_display'] = 'invisible';
      }
    }

    // Handle filter value rewrites.
    if (!empty($form[$field_id]['#options']) && $this->configuration['advanced']['rewrite']['filter_rewrite_values']) {
      // Reorder options based on rewrite values, if sort options is disabled.
      $form[$field_id]['#options'] = BetterExposedFiltersHelper::rewriteOptions($form[$field_id]['#options'], $this->configuration['advanced']['rewrite']['filter_rewrite_values'], !$this->configuration['advanced']['sort_options'], $this->configuration['advanced']['rewrite']['filter_rewrite_values_key']);
      // @todo what is $selected?
      // if (isset($selected) &&
      // !isset($form[$field_id]['#options'][$selected])) {
      // Avoid "Illegal choice" errors.
      // $form[$field_id]['#default_value'] = NULL;
      // }
    }

    // Identify all exposed filter elements.
    $identifier = $filter_id;
    $exposed_label = $filter->options['expose']['label'];
    $exposed_description = $filter->options['expose']['description'];

    if ($filter->isAGroup()) {
      $identifier = $filter->options['group_info']['identifier'];
      $exposed_label = $filter->options['group_info']['label'];
      $exposed_description = $filter->options['group_info']['description'];
    }

    // If selected, collect our collapsible filter form element and put it in
    // a details' element.
    if (!empty($form[$field_id])) {
      if ($is_collapsible) {
        $details = [];
        $details[$field_id . '_collapsible'] = [
          '#type' => 'details',
          '#title' => $exposed_label,
          '#description' => $exposed_description,
          '#attributes' => [
            'class' => ['form-item'],
          ],
          '#collapsible_disable_automatic_open' => $collapsible_disable_automatic_open,
        ];

        // Retain same weight as the original fields for details.
        $pos = array_search($field_id, array_keys($form));
        $form = array_merge(array_slice($form, 0, $pos), $details, array_slice($form, $pos));
      }
    }

    // Add possible field wrapper to validate for "between" operator.
    $element_wrapper = $field_id . '_wrapper';

    $filter_elements = [
      $identifier,
      $element_wrapper,
      $filter->options['expose']['operator_id'],
    ];

    // Iterate over all exposed filter elements.
    foreach ($filter_elements as $element) {
      // Sanity check to make sure the element exists.
      if (empty($form[$element])) {
        continue;
      }

      // "Between" operator fields to validate for.
      $fields = ['min', 'max'];

      // Check if the element is a part of a wrapper.
      $wrapper_array = $form[$element];
      if ($element === $element_wrapper) {
        // Determine if wrapper element has min or max fields or if
        // collapsible, if so then update type.
        if (array_intersect($fields, array_keys($wrapper_array[$field_id])) || $is_collapsible) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }
      else {
        // Determine if element has min or max child fields,
        // if so then update type.
        if (array_intersect($fields, array_keys($form[$field_id]))) {
          $form[$element] = [
            '#type' => 'container',
            $element => $wrapper_array,
          ];
        }
      }

      // Handle secondary elements first.
      if ($is_secondary) {
        if ($is_collapsible) {
          $this->addElementToGroup($form, $form_state, $field_id . '_collapsible', 'secondary');
        }
        else {
          $this->addElementToGroup($form, $form_state, $element, 'secondary');
        }
      }

      // Move collapsible elements.
      if ($is_collapsible) {
        $this->addElementToGroup($form, $form_state, $element, $field_id . '_collapsible');
      }
      else {
        $form[$element]['#title'] = $exposed_label;
        $form[$element]['#description'] = $exposed_description;
      }

      // Finally, add some metadata to the form element.
      $this->addContext($form[$element]);
    }
  }

  /**
   * Sorts the options for a given form element alphabetically.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   The altered element.
   */
  public function processSortedOptions(array $element, FormStateInterface $form_state): array {
    $options = &$element['#options'];

    // Ensure "- Any -" value does not get sorted.
    $any_option = FALSE;
    if (empty($element['#required'])) {
      // We use array_slice to preserve they keys needed to determine the value
      // when using a filter (e.g. taxonomy terms).
      $any_option = array_slice($options, 0, 1, TRUE);
      // Array_slice does not modify the existing array, we need to remove the
      // option manually.
      unset($options[key($any_option)]);
    }

    // Not all option arrays will have simple data types. We perform a custom
    // sort in case users want to sort more complex fields
    // (example taxonomy terms).
    if (!empty($element['#nested'])) {
      $delimiter = $element['#nested_delimiter'] ?? '-';
      $options = BetterExposedFiltersHelper::sortNestedOptions($options, $delimiter);
    }
    else {
      $options = BetterExposedFiltersHelper::sortOptions($options);
    }

    // Restore the "- Any -" value at the first position.
    if ($any_option) {
      $options = $any_option + $options;
    }

    return $element;
  }

  /**
   * Helper function to get the unique identifier for the exposed filter.
   *
   * Takes into account grouped filters with custom identifiers.
   */
  protected function getExposedFilterFieldId() {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $field_id = $filter->options['expose']['identifier'];
    $is_grouped_filter = $filter->options['is_grouped'];

    // Grouped filters store their identifier elsewhere.
    if ($is_grouped_filter) {
      $field_id = $filter->options['group_info']['identifier'];
    }

    return $field_id;
  }

  /**
   * Helper function to get the widget type of the exposed filter.
   *
   * @return string
   *   The type of the form render element use for the exposed filter.
   */
  protected function getExposedFilterWidgetType(): string {
    // We need to dig into the exposed form configuration to retrieve the
    // form type of the filter.
    $form = [];
    $form_state = new FormState();
    $form_state->set('exposed', TRUE);
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $filter->buildExposedForm($form, $form_state);
    $filter_id = $filter->options['expose']['identifier'];

    return $form[$filter_id]['#type'] ?? $form[$filter_id]['value']['#type'] ?? '';
  }

}
