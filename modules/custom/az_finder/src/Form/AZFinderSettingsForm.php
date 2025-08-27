<?php

declare(strict_types=1);

namespace Drupal\az_finder\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\az_finder\Service\AZFinderOverrides;
use Drupal\az_finder\Service\AZFinderViewOptions;
use Drupal\az_finder\Service\AZFinderVocabulary;
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
    parent::__construct($config_factory, $typed_config_manager);
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
      '#markup' => $this->t('
        <p>Manage the settings you would like to use with exposed AZ Finder forms.</p>
        <p>For more information about the Quickstart Finder, please <a href="https://quickstart.arizona.edu/create-content/quickstart-finder">visit the Quickstart website</a>.</p>
      '),
    ];

    // How these settings work section.
    $form['how_it_works'] = [
      '#type' => 'details',
      '#title' => $this->t('How do these settings work?'),
      '#open' => FALSE,
      '#description' => $this->t('
        <p>The default settings are applied to all taxonomy vocabularies as a starting point.</p>
        <p>Each Finder view display can have custom overrides to expand or collapse specific sections by default.</p>
      '),
    ];

    // Filter Widget Settings section.
    $form['az_finder_tid_widget'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter Widget Settings'),
      '#open' => TRUE,
    ];

    // Default state select field.
    $form['az_finder_tid_widget']['default_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Display of Parent Terms'),
      '#description' => $this->t('Choose how taxonomy terms with children should behave by default everywhere.<br />These settings are not context aware, so if you choose collapsed, your term must be using a collapsible element for this to work.'),
      '#weight' => -2,
      '#options' => [
        'expand' => $this->t('Expanded'),
        'collapse' => $this->t('Collapsed'),
      ],
      '#config_target' => 'az_finder.settings:tid_widget.default_state',
    ];

    // Fetch existing overrides from the AZFinderOverrides service.
    $config_overrides = $this->azFinderOverrides->getExistingOverrides();

    // Get current overrides from form state.
    $session_overrides = $form_state->getValue(['az_finder_tid_widget', 'overrides']) ?? [];

    // Normalize session overrides structure if needed.
    $normalized_session_overrides = [];
    foreach ($session_overrides as $key => $override) {
      // Filter out non-override form elements.
      if (empty($override['view_id'])) {
        continue;
      }
      $normalized_session_overrides[$key] = $override;
    }

    // Combine overrides. Session overrides will not overwrite existing ones.
    $overrides = $config_overrides + $normalized_session_overrides;

    $form['az_finder_tid_widget']['overrides'] = [
      '#type' => 'container',
      '#prefix' => '<div id="js-overrides-container">',
      '#suffix' => '</div>',
    ];

    $form['az_finder_tid_widget']['overrides']['select_view_display_container'] = [
      '#type' => 'container',
      'select_view_display' => [
        '#type' => 'select',
        '#title' => $this->t('Add an Override'),
        '#description' => $this->t('Select a particular filter widget to override the default display for each taxonomy term.'),
        '#weight' => -1,
        '#options' => $this->azFinderViewOptions->getViewOptions(),
        '#empty_option' => $this->t('- Select -'),
        '#attributes' => [
          'id' => 'js-az-select-view-display',
        ],
      ],
      'override' => [
        '#type' => 'submit',
        '#value' => $this->t('Add Override'),
        '#ajax' => [
          'callback' => '::ajaxAddOverride',
          'wrapper' => 'js-overrides-container',
          'effect' => 'fade',
        ],
        '#submit' => ['::submitOverride'],
        '#attributes' => [
          'class' => [
            'button',
            'button--primary',
            'button--small',
          ],
        ],
        '#states' => [
          'disabled' => [
            ':input[name="az_finder_tid_widget[overrides][select_view_display_container][select_view_display]"]' => ['value' => ''],
          ],
        ],
      ],
    ];

    // Add tooltip if we have overrides.
    if (!empty($overrides)) {
      $form['az_finder_tid_widget']['overrides']['configure_overrides'] = [
        '#type' => 'item',
        '#title' => $this->t('Configure Added Overrides'),
      ];
    }
    // Add override sections.
    foreach ($overrides as $override) {
      $this->addOverrideSection($form, $form_state, $override);
    }

    // Save combined overrides to the form state.
    $form_state->setValue(['az_finder_tid_widget', 'overrides'], $overrides);
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

    // Create the override for form state.
    $override = [
      'view_id' => $view_id,
      'display_id' => $display_id,
    ];

    // Ensure the overrides array is present in the form state.
    $overrides = $form_state->getValue(['az_finder_tid_widget', 'overrides']) ?? [];
    // Update the overrides with the new override.
    $overrides["$view_id:$display_id"] = $override;
    $form_state->setValue(['az_finder_tid_widget', 'overrides'], $overrides);
    $form_state->setRebuild(TRUE);

    // Optionally, provide feedback or perform additional actions.
    $this->messenger()->addMessage($this->t('Override created for @view_display.', [
      '@view_display' => $form_state->getCompleteForm()['az_finder_tid_widget']['overrides']['select_view_display_container']['select_view_display']['#options'][$selected_view_display],
    ]));
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
   * @return ?array
   *   Return the override section or null.
   */
  public function addOverrideSection(array &$form, FormStateInterface $form_state, array $override): ?array {
    $key = "{$override['view_id']}:{$override['display_id']}";
    $view_id = $override['view_id'];
    $display_id = $override['display_id'];
    if ($key !== ':' && !isset($form['az_finder_tid_widget']['overrides'][$key])) {
      $form['az_finder_tid_widget']['overrides'][$key] = [
        '#type' => 'details',
        '#title' => $this->t("Override Settings for :view_label (:display_title)", [
          ":view_label" => $override['view_label'],
          ":display_title" => $override['display_title'],
        ]),
        '#open' => FALSE,
        '#description' => $this->t('Overrides are grouped by taxonomy vocabulary. Each vocabulary can have its own settings for how filter widgets behave when they have child terms.'),
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

    $overrides = $form_state->getValue(['az_finder_tid_widget', 'overrides']) ?? [];
    $form_state->setValue(['az_finder_tid_widget', 'overrides'], $overrides);
    return $form['az_finder_tid_widget']['overrides'][$key];
  }

  /**
   * Ajax callback for the delete button.
   */
  public function ajaxDeleteOverride(array &$form, FormStateInterface $form_state) {
    // Return the updated overrides container.
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
    $overrides = $form_state->getValue(['az_finder_tid_widget', 'overrides']) ?? [];
    unset($overrides[$key]);
    $form_state->setValue(['az_finder_tid_widget', 'overrides'], $overrides);
    $form_state->setRebuild(TRUE);
  }

}
