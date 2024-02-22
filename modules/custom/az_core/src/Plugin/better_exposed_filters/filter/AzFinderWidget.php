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
      'level_0_expand_color' => '#1E5288',
      'level_0_collapse_color' => '#1E5288',
      'level_1_expand_color' => '#1E5288',
      'level_1_collapse_color' => '#1E5288',
      'level_0_expand_title' => $this->t('Level 0 Expand'),
      'level_0_collapse_title' => $this->t('Level 0 Collapse'),
      'level_1_expand_title' => $this->t('Level 1 Expand'),
      'level_1_collapse_title' => $this->t('Level 1 Collapse'),
    ];
  }

/**
 * {@inheritdoc}
 */
public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
  $form = parent::buildConfigurationForm($form, $form_state);

  // Add custom settings for the Finder widget.
  $form['help'] = [
    '#markup' => $this->t('This widget allows you to use the Finder widget for hierarchical taxonomy terms.'),
  ];
  // Define fields for SVG colors and titles
  $svg_settings = [
    'level_0_expand' => 'Level 0 Expand',
    'level_0_collapse' => 'Level 0 Collapse',
    'level_1_expand' => 'Level 1 Expand',
    'level_1_collapse' => 'Level 1 Collapse',
  ];

  foreach ($svg_settings as $key => $label) {
    $form[$key . '_color'] = [
      '#type' => 'color',
      '#list' => 'colors',
      '#title' => $this->t('@label Icon Color', ['@label' => $label]),
      '#default_value' => $this->configuration[$key . '_color'] ?? '#1E5288', // Default azurite color
      '#description' => $this->t('Specify the fill color for the @label SVG icon.', ['@label' => $label]),
    ];

    $form[$key . '_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('@label Icon Title', ['@label' => $label]),
      '#default_value' => $this->configuration[$key . '_title'] ?? $this->t('@label', ['@label' => $label]),
      '#description' => $this->t('Specify the title for the @label SVG icon.', ['@label' => $label]),
    ];
  }

  return $form;
}

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $config = $this->getConfiguration();
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

      // Set the SVG icon colors and titles.
      $form[$field_id]['#level_0_expand_color'] = $config['level_0_expand_color'];
      $form[$field_id]['#level_0_collapse_color'] = $config['level_0_collapse_color'];
      $form[$field_id]['#level_1_expand_color'] = $config['level_1_expand_color'];
      $form[$field_id]['#level_1_collapse_color'] = $config['level_1_collapse_color'];
      $form[$field_id]['#level_0_expand_title'] = $config['level_0_expand_title'];
      $form[$field_id]['#level_0_collapse_title'] = $config['level_0_collapse_title'];
      $form[$field_id]['#level_1_expand_title'] = $config['level_1_expand_title'];
      $form[$field_id]['#level_1_collapse_title'] = $config['level_1_collapse_title'];

      // Render as checkboxes if filter allows multiple selections.
      if (!empty($form[$field_id]['#multiple'])) {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'checkboxes';
      }
      // Else render as radio buttons.
      else {
        $form[$field_id]['#theme'] = 'az_finder_widget';
        $form[$field_id]['#type'] = 'radios';
      }
    }
  }

}
