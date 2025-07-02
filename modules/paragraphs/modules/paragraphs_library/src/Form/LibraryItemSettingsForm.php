<?php

namespace Drupal\paragraphs_library\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;

/**
 * Form for Paragraphs library item settings.
 */
class LibraryItemSettingsForm extends ConfigFormBase {

  /**
   * The entity reference selection plugin manager.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $pluginManagerEntityReferenceSelection;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new LibraryItemBaseParagraphOverrideForm object.
   *
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $plugin_manager_entity_reference_selection
   *   The selection plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(
    SelectionPluginManagerInterface $plugin_manager_entity_reference_selection,
    EntityFieldManagerInterface $entity_field_manager
  ) {
    $this->entityFieldManager = $entity_field_manager;
    $this->pluginManagerEntityReferenceSelection = $plugin_manager_entity_reference_selection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paragraphs_library_item_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $paragraph_selection_handler = $this->getParagraphSelectionHandler();

    // Set the form state to match the existing paragraph selection handler
    // settings.
    $fields = $this->entityFieldManager->getFieldDefinitions('paragraphs_library_item', 'paragraphs_library_item');
    $settings = $fields['paragraphs']->getConfig('paragraphs_library_item')->getSettings();
    if (!empty($settings['handler_settings'])) {
      // Since we are building a handler's form, have to move handler settings
      // up to the same level as the rest of the config.
      $handler_settings = $settings['handler_settings'];
      unset($settings['handler_settings']);
      $settings = array_merge($settings, $handler_settings);
      $paragraph_selection_handler->setConfiguration($settings);
    }

    // Build this form using the ParagraphsSelection form.
    $form = $paragraph_selection_handler->buildConfigurationForm($form, $form_state);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get an instance of the paragraph selection handler.
   *
   * @return false|\Drupal\paragraphs\Plugin\EntityReferenceSelection\ParagraphSelection
   *   The paragraph selection handler.
   */
  protected function getParagraphSelectionHandler() {
    // Get an instance of the ParagraphsSelection handler.
    $options = [
      'target_type' => 'paragraph',
      'handler' => 'default:paragraph',
    ];
    return $this->pluginManagerEntityReferenceSelection->getInstance($options);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get only the values related to the paragraph selection handler form.
    $values = $form_state->getValues();
    $paragraph_selection_handler = $this->getParagraphSelectionHandler();
    $paragraph_selection_form = $paragraph_selection_handler->buildConfigurationForm([], $form_state);
    $paragraph_selection_form_values = array_intersect_key($values, $paragraph_selection_form);

    // Get existing paragraphs selection settings.
    $fields = $this->entityFieldManager->getFieldDefinitions('paragraphs_library_item', 'paragraphs_library_item');
    $paragraphs_config = $fields['paragraphs']->getConfig('paragraphs_library_item');
    $settings = $paragraphs_config->getSettings();

    // Save the new handler settings.
    $settings['handler_settings'] = $paragraph_selection_form_values;
    $settings['handler'] = 'default:paragraph';
    $paragraphs_config->setSettings($settings)->save();

    $this->entityFieldManager->clearCachedFieldDefinitions();
  }

}
