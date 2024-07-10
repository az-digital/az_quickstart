<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\az_finder\Service\AZFinderOverrides;
use Drupal\az_finder\Service\AZFinderViewOptions;
use Drupal\az_finder\Service\AZFinderVocabulary;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for custom Quickstart Finder module settings.
 */
class AZFinderSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The AZFinderViewOptions service.
   *
   * @var \Drupal\az_finder\Service\AZFinderViewOptions
   */
  protected $azFinderViewOptions;

  /**
   * The AZFinderVocabulary service.
   *
   * @var \Drupal\az_finder\Service\AZFinderVocabulary
   */
  protected $azFinderVocabulary;

  /**
   * The AZFinderOverrides service.
   *
   * @var \Drupal\az_finder\Service\AZFinderOverrides
   */
  protected $azFinderOverrides;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AZFinderSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\az_finder\Service\AZFinderViewOptions $az_finder_view_options
   *   The AZFinderViewOptions service.
   * @param \Drupal\az_finder\Service\AZFinderVocabulary $az_finder_vocabulary
   *   The AZFinderVocabulary service.
   * @param \Drupal\az_finder\Service\AZFinderOverrides $az_finder_overrides
   *   The AZFinderOverrides service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typed_config_manager,
    AZFinderViewOptions $az_finder_view_options,
    AZFinderVocabulary $az_finder_vocabulary,
    AZFinderOverrides $az_finder_overrides,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($config_factory);
    $this->typedConfigManager = $typed_config_manager;
    $this->azFinderViewOptions = $az_finder_view_options;
    $this->azFinderVocabulary = $az_finder_vocabulary;
    $this->azFinderOverrides = $az_finder_overrides;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('az_finder.view_options'),
      $container->get('az_finder.vocabulary'),
      $container->get('az_finder.overrides'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'az_finder_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['az_finder.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#tree'] = TRUE;

    // Page description.
    $form['description'] = [
      '#type' => 'item',
      '#markup' => t('Manage the settings you would like to use with exposed AZ Finder forms.'),
    ];

    // How this feature works section.
    $form['how_it_works'] = [
      '#type' => 'details',
      '#title' => $this->t('How does this feature work?'),
      '#open' => FALSE,
      '#description' => $this->t('Details on how it works.'),
    ];

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
        'expand' => $this->t('Expanded'),
        'collapse' => $this->t('Collapsed'),
      ],
      '#empty_option' => $this->t('- Select -'),
      '#description' => $this->t('Choose how term ID widgets should behave by default everywhere. These settings are not context aware, so if you choose collapse, your term must be using a collapsible element for this to work.'),
      '#config_target' => 'az_finder.settings:tid_widget.default_state',
    ];

    // Fetch existing overrides from the AZFinderOverrides service.
    $config_overrides = $this->azFinderOverrides->getExistingOverrides();

    // Get current overrides from form state.
    $session_overrides = $form_state->get('overrides') ?? [];

    // Combine overrides.
    $overrides = array_merge($config_overrides, $session_overrides);

    $form['az_finder_tid_widget']['overrides'] = [
      '#type' => 'container',
      '#prefix' => '<div id="js-overrides-container">',
      '#suffix' => '</div>',
    ];

    $form['az_finder_tid_widget']['overrides']['select_view_display_container'] = [
      '#type' => 'container',
      '#prefix' => '<div class="container-inline">',
      '#suffix' => '</div>',
      'select_view_display' => [
        '#type' => 'select',
        '#title' => $this->t('Select View and Display'),
        '#options' => $this->azFinderViewOptions->getViewOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#attributes' => ['id' => 'js-az-select-view-display'],
      ],
      'override' => [
        '#type' => 'submit',
        '#value' => $this->t('Override'),
        '#ajax' => [
          'callback' => '::ajaxAddOverride',
          'wrapper' => 'js-overrides-container',
          'effect' => 'fade',
        ],
        '#submit' => ['::submitOverride'],
        '#attributes' => [
          'class' => [
            'button',
            'button--primary'
          ],
        ],
        '#states' => [
          'disabled' => [
            ':input[name="az_finder_tid_widget[overrides][select_view_display_container][select_view_display]"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    // Add override sections.
    foreach ($overrides as $override) {
      $this->addOverrideSection($form, $form_state, $override);
    }

    // Save combined overrides to the form state.
    $form_state->set('overrides', $overrides);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Separate submit handler for the override button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitOverride(array &$form, FormStateInterface $form_state) {
    // Retrieve selected view and display.
    $selected_view_display = $form_state->getValue([
      'description',
      'how_it_works',
      'az_finder_tid_widget',
      'overrides',
      'select_view_display_container',
      'select_view_display',
    ]);
    [$view_id, $display_id] = explode(':', $selected_view_display);

    // Prepare the configuration key.
    $config_name = "az_finder.tid_widget.$view_id.$display_id";

    // Initialize or load existing configuration.
    $config = $this->configFactory->getEditable($config_name);

    // Save the configuration.
    $config->save();

    // Optionally, provide feedback or perform additional actions.
    $this->messenger()->addMessage($this->t('Override created for @view_display.', ['@view_display' => $selected_view_display]));

    // Rebuild the form to reflect changes.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback for the override button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The updated overrides container.
   */
  public function ajaxAddOverride(array &$form, FormStateInterface $form_state): array {
    // Get the selected option from the form state.
    $selected_option = $form_state->getValue([
      'description',
      'how_it_works',
      'az_finder_tid_widget',
      'overrides',
      'select_view_display_container',
      'select_view_display',
    ]);

    // Split the selected option into view_id and display_id.
    [$view_id, $display_id] = explode(':', $selected_option);
    $override = [
      'view_id' => $view_id,
      'display_id' => $display_id,
      'origin' => 'session',
    ];

    // Ensure the overrides array is present in the form state.
    $overrides = $form_state->get('overrides') ?? [];

    // Update the overrides with the new override.
    $overrides["$view_id:$display_id"] = $override;
    $form_state->set('overrides', $overrides);

    // Add the override section to the form.
    $this->addOverrideSection($form, $form_state, $override);

    // Set the rebuild flag to ensure the form is rebuilt.
    $form_state->setRebuild(TRUE);

    // Return the updated overrides container.
    return $form['az_finder_tid_widget']['overrides'];
  }

  /**
   * Add an override section to the form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $override
   *   The override data.
   *
   * @return array
   *   The form array.
   */
  public function addOverrideSection(array &$form, FormStateInterface $form_state, array $override) {
    $key = "{$override['view_id']}:{$override['display_id']}";
    $view_id = $override['view_id'];
    $display_id = $override['display_id'];

    if (!isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for :view_id - :display_id", [
          ":view_id" => $view_id,
          ":display_id" => $display_id,
        ]),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by vocabulary. Each vocabulary can have its own settings for how term ID widgets behave when they have child terms.'),
        '#tree' => TRUE,
      ];
      $form['az_finder_tid_widget']['overrides'][$key]['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete'),
        '#ajax' => [
          'callback' => '::ajaxDeleteOverride',
          'wrapper' => 'js-overrides-container',
          'effect' => 'fade',
        ],
        '#name' => 'delete-' . $key,
        '#submit' => ['::submitDeleteOverride'],
        '#attributes' => [
          'class' => ['button--small'],
        ],
      ];

      $config_name = "az_finder.tid_widget.{$view_id}.{$display_id}";
      $config = $this->config($config_name);
      $vocabulary_ids = $this->azFinderVocabulary->getVocabularyIdsForFilter($view_id, $display_id, 'taxonomy_index_tid');

      foreach ($vocabulary_ids as $vocabulary_id) {
        $this->azFinderVocabulary->addTermsTable(
              $form['az_finder_tid_widget']['overrides'][$key]['vocabularies'][$vocabulary_id],
              $vocabulary_id,
              $view_id,
              $display_id
          );

      }

    }
    $overrides = $form_state->get('overrides') ?? [];
    $form_state->set('overrides', $overrides);
    $form_state->setRebuild(TRUE);
    return $form['az_finder_tid_widget']['overrides'][$key];
  }

  /**
   * Ajax callback for the delete button.
   */
  public function ajaxDeleteOverride(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
    $key = str_replace('delete-', '', $button_name);
    $overrides = $form_state->get('overrides') ?? [];
    unset($overrides[$key]);
    unset($form['az_finder_tid_widget']['overrides'][$key]);
    $this->configFactory->getEditable('az_finder.tid_widget.' . $key)->delete();
    $form_state->set('overrides', $overrides);
    // Rebuild the form.
    $form_state->setRebuild(TRUE);

    return $form['az_finder_tid_widget']['overrides'];
  }

  /**
   * Separate submit handler for the delete button.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function submitDeleteOverride(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $button_name = $triggering_element['#name'];
    $key = str_replace('delete-', '', $button_name);
    [$view_id, $display_id] = explode(':', $key);
    $config_name = "az_finder.tid_widget.$view_id.$display_id";
    $config = $this->config($config_name);
    if ($config) {
      $editable_config = $this->configFactory->getEditable($config_name);
      $editable_config->delete();
    }
    // Update the overrides in form state.
    $overrides = $form_state->get('overrides') ?? [];
    unset($overrides[$key]);
    $form_state->set('overrides', $overrides);

    // Set the rebuild flag to ensure the form is rebuilt.
    $form_state->setRebuild(TRUE);
  }

}
