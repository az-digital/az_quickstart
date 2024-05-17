<?php


declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\az_finder\Service\AZFinderViewOptions;
use Drupal\az_finder\Service\AZFinderVocabulary;
use Drupal\az_finder\Service\AZFinderOverrides;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom Quickstart Finder module settings.
 */
class AZFinderSettingsForm extends ConfigFormBase {

  protected $viewOptions;
  protected $vocabulary;
  protected $overrides;

  public function __construct(
    AZFinderViewOptions $view_options,
    AZFinderVocabulary $vocabulary,
    AZFinderOverrides $overrides
  ) {
    $this->viewOptions = $view_options;
    $this->vocabulary = $vocabulary;
    $this->overrides = $overrides;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('az_finder.view_options'),
      $container->get('az_finder.vocabulary'),
      $container->get('az_finder.overrides')
    );
  }

  public function getFormId() {
    return 'az_finder_settings';
  }

  protected function getEditableConfigNames() {
    return ['az_finder.settings'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  public function overrideSettingsCallback(array &$form, FormStateInterface $form_state) {
    $selected_view = $form_state->getValue(['az_finder_tid_widget', 'overrides', 'select_view_display_container', 'select_view_display']);
    if ($selected_view && strpos($selected_view, ':') !== false) {
      [$view_id, $display_id] = explode(':', $selected_view);

      if (!empty($view_id) && !empty($display_id)) {
        $key = $view_id . '_' . $display_id;
        $overrides = $form_state->get('overrides') ?? [];
        $overrides[$key] = ['view_id' => $view_id, 'display_id' => $display_id];
        $form_state->set('overrides', $overrides);

        $this->addOverrideSection($form, $form_state, $key, $view_id, $display_id);
      }
    }

    $form_state->setRebuild(TRUE);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('#js-az-finder-tid-widget-overrides-container', $form['az_finder_tid_widget']['overrides']));
    return $response;
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

    $form['az_finder_tid_widget']['overrides'] = [
      '#prefix' => '<div id="js-az-finder-tid-widget-overrides-container">',
      '#suffix' => '</div>',
    ];

    if (!$form_state->has('az_finder_tid_widget', 'overrides')) {
      $overrides = $this->overrides->getExistingOverrides();
      $form_state->set('az_finder_tid_widget', 'overrides', $overrides);
    } else {
      $overrides = $form_state->get('az_finder_tid_widget', 'overrides') ?? [];
    }

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
        '#options' => $this->viewOptions->getViewOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#attributes' => ['id' => 'js-az-select-view-display'],
      ],
      'override' => [
        '#type' => 'button',
        '#value' => $this->t('Override'),
        '#ajax' => [
          'callback' => '::overrideSettingsCallback',
          'wrapper' => 'js-az-finder-tid-widget-overrides-container',
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

  protected function addOverrideSection(&$form, FormStateInterface $form_state, $key, $view_id, $display_id) {
    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for $view_id - $display_id"),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
        '#tree' => TRUE,
        '#config_target' => "az_finder.tid_widget.$view_id.$display_id",
      ];

      $vocabulary_ids = $this->vocabularyService->getVocabularyIdsForFilter($view_id, $display_id, 'az_finder_tid_widget');
      foreach ($vocabulary_ids as $vocabulary_id) {
        $this->vocabulary->addTermsTable($form['az_finder_tid_widget']['overrides'][$key], $vocabulary_id, $view_id, $display_id, $this->config("az_finder.tid_widget.$view_id.$display_id"));
      }
    }
  }
}
