<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

use Drupal\az_exposed_filters\AzExposedFiltersHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "az_exposed_filters",
 *   label = @Translation("Checkboxes/Radio Buttons"),
 * )
 */
class RadioButtons extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'select_all_none' => FALSE,
      'select_all_none_nested' => FALSE,
      'display_inline' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['select_all_none'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add select all/none links'),
      '#default_value' => !empty($this->configuration['select_all_none']),
      '#disabled' => !$filter->options['expose']['multiple'],
      '#description' => $this->t('Add a "Select All/None" link when rendering the exposed filter using checkboxes. If this option is disabled, edit the filter and check the "Allow multiple selections".'
      ),
    ];

    $form['select_all_none_nested'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add nested all/none selection'),
      '#default_value' => !empty($this->configuration['select_all_none_nested']),
      '#disabled' => (!$filter->options['expose']['multiple']) || (isset($filter->options['hierarchy']) && !$filter->options['hierarchy']),
      '#description' => $this->t('When a parent checkbox is checked, check all its children. If this option is disabled, edit the filter and check "Allow multiple selections" and edit the filter settings and check "Show hierarchy in dropdown".'
      ),
    ];

    $form['display_inline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display inline'),
      '#default_value' => !empty($this->configuration['display_inline']),
      '#description' => $this->t('Display checkbox/radio options inline.'
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    // Form element is designated by the element ID which is user-
    // configurable.
    $field_id = $filter->options['is_grouped'] ? $filter->options['group_info']['identifier'] : $filter->options['expose']['identifier'];

    parent::exposedFormAlter($form, $form_state);

    if (!empty($form[$field_id])) {
      // Clean up filters that pass objects as options instead of strings.
      if (!empty($form[$field_id]['#options'])) {
        $form[$field_id]['#options'] = AzExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
      }

      // Support rendering hierarchical checkboxes/radio buttons (e.g. taxonomy
      // terms).
      if (!empty($filter->options['hierarchy'])) {
        $form[$field_id]['#az_exposed_filters_nested'] = TRUE;
      }

      // Display inline.
      $form[$field_id]['#az_exposed_filters_display_inline'] = $this->configuration['display_inline'];

      // Render as checkboxes if filter allows multiple selections.
      if (!empty($form[$field_id]['#multiple'])) {
        $form[$field_id]['#theme'] = 'az_exposed_filters_checkboxes';
        $form[$field_id]['#type'] = 'checkboxes';

        // Show all/none option.
        $form[$field_id]['#az_exposed_filters_select_all_none'] = $this->configuration['select_all_none'];
        $form[$field_id]['#az_exposed_filters_select_all_none_nested'] = $this->configuration['select_all_none_nested'];

        // Attach the JS (@see /js/az_exposed_filters_select_all_none.js)
        $form['#attached']['library'][] = 'az_exposed_filters/select_all_none';
      }
      // Else render as radio buttons.
      else {
        $form[$field_id]['#theme'] = 'az_exposed_filters_radios';
        $form[$field_id]['#type'] = 'radios';
      }
    }
  }

}
