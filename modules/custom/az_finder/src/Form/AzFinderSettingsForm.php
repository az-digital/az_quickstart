<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $form['az_finder_tid_widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Term ID Widget Settings'),
      '#open' => TRUE,
      '#description' => $this->t('Configure the default settings for term ID widgets.'),
    ];

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
      '#default_value' => $config->get('tid_widget.default_state'),
    ];

    // Ensure overrides are properly initialized in the form state.
    if (!$form_state->has('overrides')) {
      $form_state->set('overrides', []);
    }
    $overrides = $form_state->get('overrides');

    $wrapper_id = 'js-az-finder-tid-widget-overrides-container';
    $form['az_finder_tid_widget']['overrides'] = [
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    foreach ($overrides as $key => $override) {
      $this->addOverrideSection($form, $form_state, $key, $override['view_id'], $override['display_id']);
    }

    $form['az_finder_tid_widget']['overrides']['select_view_display_container'] = [
      '#type' => 'container',
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      'select_view_display' => [
        '#type' => 'select',
        '#title' => $this->t('Select View and Display'),
        '#options' => [
          'view1:display1' => $this->t('View 1 - Display 1'),
          'view2:display2' => $this->t('View 2 - Display 2'),
        ],
        '#empty_option' => $this->t('- Select -'),
        '#attributes' => ['id' => 'js-az-select-view-display'],
      ],
      'override' => [
        '#type' => 'button',
        '#value' => $this->t('Override'),
        '#ajax' => [
          'callback' => '::overrideSettingsCallback',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#states' => [
          'disabled' => [
            ':input[id="js-az-select-view-display"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function overrideSettingsCallback(array &$form, FormStateInterface $form_state) {
    $selected_view = $form_state->getValue(['az_finder_tid_widget', 'overrides', 'select_view_display_container', 'select_view_display']);
    if ($selected_view && strpos($selected_view, ':') !== false) {
      [$view_id, $display_id] = explode(':', $selected_view);

      if (!empty($view_id) && !empty($display_id)) {
        $key = $view_id . '_' . $display_id;
        $overrides = $form_state->get('overrides') ?? [];
        if (!isset($overrides[$key])) {
          $overrides[$key] = ['view_id' => $view_id, 'display_id' => $display_id, 'vocabularies' => []];
        }
        $form_state->set('overrides', $overrides);

        $this->addOverrideSection($form, $form_state, $key, $view_id, $display_id);
      }
    }

    $form_state->setRebuild(TRUE);

    // Rebuild the select_view_display with new options and reset selection
    $form['az_finder_tid_widget']['overrides']['select_view_display_container']['select_view_display']['#options'] = [
      'view1:display1' => $this->t('View 1 - Display 1'),
      'view2:display2' => $this->t('View 2 - Display 2'),
    ];
    $form['az_finder_tid_widget']['overrides']['select_view_display_container']['select_view_display']['#value'] = '';

    $wrapper_id = 'js-az-finder-tid-widget-overrides-container';
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#' . $wrapper_id, $form['az_finder_tid_widget']['overrides']));
    return $response;
  }

  protected function addOverrideSection(&$form, FormStateInterface $form_state, $key, $view_id, $display_id) {
    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for $view_id - $display_id"),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
        '#tree' => TRUE,
      ];

      // For simplicity, add dummy vocabularies and terms
      $vocabularies = ['vocab1' => ['term1', 'term2'], 'vocab2' => ['term3', 'term4']];
      foreach ($vocabularies as $vocabulary_id => $terms) {
        $form['az_finder_tid_widget']['overrides'][$key][$vocabulary_id] = [
          '#type' => 'details',
          '#title' => $this->t("Vocabulary: $vocabulary_id"),
          '#open' => FALSE,
        ];

        foreach ($terms as $term_id) {
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
          ];
        }
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Debugging: Print form state values
    dpm($form_state->getValues(), 'Form State Values');

    // Save the default state setting
    $this->config('az_finder.settings')
      ->set('tid_widget.default_state', $form_state->getValue(['az_finder_tid_widget', 'default_state']))
      ->save();

    // Save the overrides
    $overrides = $form_state->getValue(['az_finder_tid_widget', 'overrides']);
    dpm($overrides, 'Overrides in submitForm');
  }
}
