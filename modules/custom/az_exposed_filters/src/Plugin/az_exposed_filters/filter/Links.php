<?php

namespace Drupal\az_exposed_filters\Plugin\az_exposed_filters\filter;

use Drupal\az_exposed_filters\AzExposedFiltersHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default widget implementation.
 *
 * @AzExposedFiltersFilterWidget(
 *   id = "az_exposed_filters_links",
 *   label = @Translation("Links"),
 * )
 */
class Links extends FilterWidgetBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'select_all_none' => FALSE,
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exposedFormAlter(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\views\Plugin\views\filter\FilterPluginBase $filter */
    $filter = $this->handler;
    $field_id = $this->getExposedFilterFieldId();

    parent::exposedFormAlter($form, $form_state);

    if (!empty($form[$field_id])) {
      // Clean up filters that pass objects as options instead of strings.
      if (!empty($form[$field_id]['#options'])) {
        $form[$field_id]['#options'] = AzExposedFiltersHelper::flattenOptions($form[$field_id]['#options']);
      }

      // Support rendering hierarchical links (e.g. taxonomy terms).
      if (!empty($filter->options['hierarchy'])) {
        $form[$field_id]['#az_exposed_filters_nested'] = TRUE;
      }

      $form[$field_id]['#theme'] = 'az_exposed_filters_links';
      // Exposed form displayed as blocks can appear on pages other than
      // the view results appear on. This can cause problems with
      // select_as_links options as they will use the wrong path. We
      // provide a hint for theme functions to correct this.
      $form[$field_id]['#az_exposed_filters_path'] = $this->getExposedFormActionUrl($form_state);

      if ($filter->view->ajaxEnabled() || $filter->view->display_handler->ajaxEnabled()) {
        $form[$field_id]['#attributes']['class'][] = 'az-exposed-filters-links-use-ajax';
        $form['#attached']['library'][] = 'az_exposed_filters/links_use_ajax';
      }
    }
  }

}
