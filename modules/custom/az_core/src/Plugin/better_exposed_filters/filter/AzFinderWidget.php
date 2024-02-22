<?php

namespace Drupal\az_core\Plugin\better_exposed_filters\filter;

use Drupal\better_exposed_filters\Plugin\better_exposed_filters\filter\FilterWidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\better_exposed_filters\BetterExposedFiltersHelper;
/**
 * Finder widget implementation.
 *
 * @BetterExposedFiltersFilterWidget(
 *   id = "az_finder",
 *   label = @Translation("Finder"),
 * )
 */
class AzFinderWidget extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'select_all_none' => FALSE,
      'select_all_none_nested' => FALSE,
      'display_inline' => FALSE,
      'expand_all_levels' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['help'] = [
      '#markup' => $this->t('This widget allows you to use the Finder widget for hierarchical taxonomy terms.'),
    ];
    $form['expand_all_levels'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand all hierarchical levels by default'),
      '#default_value' => $this->configuration['expand_all_levels'],
      '#description' => $this->t('When enabled, all hierarchical levels will be expanded by default.'),
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
        $form[$field_id]['#options'] = BetterExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
      }

      // Support rendering hierarchical checkboxes/radio buttons (e.g. taxonomy
      // terms).
      if (!empty($filter->options['hierarchy'])) {
        $form[$field_id]['#hierarchy'] = TRUE;
      }

      // Display inline.
      $form[$field_id]['#bef_display_inline'] = $this->configuration['display_inline'];

      // Render as checkboxes if filter allows multiple selections.
      if (!empty($form[$field_id]['#multiple'])) {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'checkboxes';

        // Show all/none option.
        $form[$field_id]['#bef_select_all_none'] = $this->configuration['select_all_none'];
        $form[$field_id]['#bef_select_all_none_nested'] = $this->configuration['select_all_none_nested'];

        // Attach the JS (@see /js/bef_select_all_none.js)
        $form['#attached']['library'][] = 'better_exposed_filters/select_all_none';
      }
      // Else render as radio buttons.
      else {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'radios';
      }
    }
  }

}
