<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for custom Quickstart Finder module settings.
 */
class AZFinderSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'az_finder_settings';
  }

  protected function getEditableConfigNames() {
    return ['az_finder.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('az_finder.settings');

    $form['#tree'] = TRUE;

    // Term ID Widget Settings section.
    $form['az_finder_tid_widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Term ID Widget Settings'),
      '#open' => TRUE,
      '#description' => $this->t('Configure the default settings for term ID widgets.'),
    ];

    // Default state select field.
    $form['az_finder_tid_widget']['default_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Default State Setting'),
      '#options' => [
        'hide' => $this->t('Hide'),
        'disable' => $this->t('Disable'),
        'remove' => $this->t('Remove'),
        'expand' => $this->t('Expand'),
        'collapse' => $this->t('Collapse'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('Choose how term ID widgets should behave by default everywhere.'),
      '#config_target' => 'az_finder.settings:tid_widget.default_state',
    ];

    // Fetch existing overrides from configuration.
    $overrides = $this->getOverridesFromConfig();

    // Save overrides to the form state.
    $form_state->set('overrides', $overrides);

    $form['az_finder_tid_widget']['overrides'] = [
      '#type' => 'container',
    ];

    // Add override sections.
    foreach ($overrides as $key => $override) {
      if ($override) {
        $this->addOverrideSection($form, $form_state, $key, $override['view_id'], $override['display_id']);
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Fetches existing overrides from configuration.
   */
  protected function getOverridesFromConfig(): array {
    $overrides = [];

    // Here, we should fetch the list of view/display overrides.
    // Assuming we have a predefined list of views and displays.
    $views_displays = [
      ['view_id' => 'view1', 'display_id' => 'display1'],
      ['view_id' => 'view2', 'display_id' => 'display2'],
    ];

    foreach ($views_displays as $view_display) {
      $view_id = $view_display['view_id'];
      $display_id = $view_display['display_id'];
      $config_name = "az_finder.tid_widget.$view_id.$display_id";
      $config = $this->config($config_name);

      if ($config->isNew() === FALSE) {
        $vocabularies = $config->get('vocabularies') ?? [];
        $overrides["$view_id:$display_id"] = [
          'view_id' => $view_id,
          'display_id' => $display_id,
          'vocabularies' => $vocabularies,
        ];
      }
    }

    return $overrides;
  }

  /**
   * Adds an override section to the form.
   */
  protected function addOverrideSection(array &$form, FormStateInterface $form_state, string $key, string $view_id, string $display_id) {
    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for $view_id - $display_id"),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
        '#tree' => TRUE,
      ];

      $vocabularies = $form_state->get('overrides')[$key]['vocabularies'] ?? [];

      foreach ($vocabularies as $vocabulary_id => $terms) {
        $form['az_finder_tid_widget']['overrides'][$key][$vocabulary_id] = [
          '#type' => 'details',
          '#title' => $this->t("Vocabulary: $vocabulary_id"),
          '#open' => FALSE,
        ];

        foreach ($terms as $term_id => $state) {
          $form['az_finder_tid_widget']['overrides'][$key][$vocabulary_id][$term_id] = [
            '#type' => 'select',
            '#title' => $this->t("Term: $term_id"),
            '#options' => [
              'hide' => $this->t('Hide'),
              'disable' => $this->t('Disable'),
              'remove' => $this->t('Remove'),
              'expand' => $this->t('Expand'),
              'collapse' => $this->t('Collapse'),
            ],
            '#empty_option' => $this->t('- Select -'),
            '#config_target' => "az_finder.tid_widget.$view_id.$display_id:vocabularies.$vocabulary_id.terms.$term_id.default_state",
          ];
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    \Drupal::messenger()->addStatus($this->t('The configuration options have been saved.'));
  }
}
