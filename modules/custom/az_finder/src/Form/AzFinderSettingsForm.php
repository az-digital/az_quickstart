<?php

declare(strict_types = 1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\taxonomy\Entity\Vocabulary;

class AZFinderSettingsForm extends FormBase {

  public function getFormId() {
    return 'az_finder_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;

    if (!$form_state->has('override_count')) {
      $form_state->set('override_count', 0);
    }

    $form['az_finder_tid_widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Term ID Widget Settings'),
      '#open' => TRUE,
    ];
    $form['az_finder_tid_widget']['default'] = [
      '#title' => $this->t('Select Default Setting'),

        '#type' => 'select',
        '#options' => [
          'hide' => $this->t('Hide'),
          'disable' => $this->t('Disable'),
          'remove' => $this->t('Remove'),
          'expand' => $this->t('Expand'),
          'collapse' => $this->t('Collapse'),
        ],
    ];

    $form['az_finder_tid_widget']['overrides'] = [
      '#type' => 'container',
      '#prefix' => '<div id="overrides-wrapper">',
      '#suffix' => '</div>',
    ];

    $override_count = $form_state->get('override_count');
    for ($i = 0; $i <= $override_count; $i++) {
      $this->addOverrideSection($form, $form_state, $i);
    }

    $form['az_finder_tid_widget']['add_override'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Override for a View Display'),
      '#submit' => ['::addViewDisplayOverride'],
    ];

    return $form;
  }

  public function addViewDisplayOverride(array &$form, FormStateInterface $form_state) {
    $form_state->set('override_count', $form_state->get('override_count') + 1);
    $form_state->setRebuild(TRUE);
  }

  protected function addOverrideSection(&$form, FormStateInterface $form_state, $index) {
    $wrapper_id = 'override-details-' . $index;

    $form['az_finder_tid_widget']['overrides']["override_$index"] = [
      '#type' => 'details',
      '#title' => $this->t('Override for View Display ' . ($index + 1)),
      '#open' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $form['az_finder_tid_widget']['overrides']["override_$index"]['select_view_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Select View and Display'),
      '#options' => $this->getViewsUsingPlugin(),
    ];
    $selected_view_display = 'az_events:az_grid';
    $vocabulary = Vocabulary::load('az_page_categories');

    if ($vocabulary) {

      $form['az_finder_tid_widget']['overrides']["override_$index"]['vocabulary_settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Settings for %vocabulary', ['%vocabulary' => $vocabulary->label()]),
        '#open' => TRUE,
      ];

      $this->buildVocabularySettingsSection($form, $index, $vocabulary);

    }
  }

protected function buildVocabularySettingsSection(&$form, $index, $vocabulary) {
  $wrapper_id = 'vocabulary-settings-' . $index;

  $form['az_finder_tid_widget']['overrides']["override_$index"]['vocabulary_settings'] = [
    '#type' => 'details',
    '#title' => $this->t('Settings for %vocabulary', ['%vocabulary' => $vocabulary->label()]),
    '#open' => TRUE,
    '#prefix' => '<div id="' . $wrapper_id . '">',
    '#suffix' => '</div>',
  ];

  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vocabulary->id());
  $table_id = 'term-settings-table-' . $index;
  $form['az_finder_tid_widget']['overrides']["override_$index"]['vocabulary_settings'][$table_id] = [
    '#type' => 'table',
    '#header' => [$this->t('Term'), $this->t('Default Setting')],
    '#empty' => $this->t('No terms found.'),
  ];

  foreach ($terms as $term) {
    $form['az_finder_tid_widget']['overrides']["override_$index"]['vocabulary_settings'][$table_id][$term->tid] = [
      'term_name' => [
        '#markup' => $term->name,
      ],
      'default_setting' => [
        '#type' => 'select',
        '#title' => $this->t('Select Default Setting for %term', ['%term' => $term->name]),
        '#title_display' => 'invisible',
        '#options' => [
          'hide' => $this->t('Hide'),
          'disable' => $this->t('Disable'),
          'remove' => $this->t('Remove'),
          'expand' => $this->t('Expand'),
          'collapse' => $this->t('Collapse'),
        ],
      ],
    ];
  }
}

  protected function getViewsUsingPlugin() {
    $options = [];
    $views = Views::getAllViews();

    foreach ($views as $view) {
      foreach ($view->get('display') as $display_id => $display) {
        $exposed_form_settings = $display['display_options']['exposed_form']['options']['bef'] ?? [];
        foreach ($exposed_form_settings['filter'] as $filter_id => $filter_settings) {
          if (!empty($filter_settings['plugin_id']) && $filter_settings['plugin_id'] === 'az_finder_tid_widget') {
            $options[$view->id() . ':' . $display_id] = $view->label() . ' (' . $display_id . ')';
            break;
          }
        }
      }
    }

    return $options;
  }

protected function getVocabularyIdFromView($view_id, $display_id) {
  $view = Views::getView($view_id);
  if (!$view) {
    dpm('View not found!');
    return NULL;
  }
  dpm($view);

  $view->setDisplay($display_id);
  $display = $view->getDisplay();

  if (!empty($display)) {
    dpm($display);
    // $handler_settings = $display->getOption('filter');
    // if (!empty($handler_settings)) {
    //   foreach ($handler_settings as $filter) {
    //     if (!empty($filter['plugin_id']) && $filter['plugin_id'] === 'az_finder_tid_widget') {
    //       if (!empty($filter['settings']['vocabulary_id'])) {
    //         return $filter['settings']['vocabulary_id']; // Return the vocabulary ID from the plugin settings
    //       }
    //     }
    //   }
    // }
  }

  return NULL; // No vocabulary ID found in the view display settings
}

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Implement your submit logic here, such as saving form data to configuration
  }
}
