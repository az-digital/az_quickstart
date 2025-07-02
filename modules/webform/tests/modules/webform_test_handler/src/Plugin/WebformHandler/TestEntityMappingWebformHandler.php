<?php

namespace Drupal\webform_test_handler\Plugin\WebformHandler;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission entity mapping test handler.
 *
 * IMPORTANT: This handler is just a POC of mapping webform elements to entity
 * fields using a Ajaxified configuration form.
 *
 * @WebformHandler(
 *   id = "test_entity_mapping",
 *   label = @Translation("Test entity mapping"),
 *   category = @Translation("Testing"),
 *   description = @Translation("Tests mapping webform element's to entity fields."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class TestEntityMappingWebformHandler extends WebformHandlerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entity_type' => 'node',
      'bundle' => 'page',
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => Yaml::encode($this->configuration),
      '#prefix' => '<pre>',
      '#suffix' => '<pre>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $this->applyFormStateToConfiguration($form_state);

    // Define #ajax callback.
    $ajax = [
      'callback' => [get_class($this), 'ajaxCallback'],
      'wrapper' => 'webform-test-ajax-container',
    ];

    /* ********************************************************************** */
    // Entity type.
    /* ********************************************************************** */

    // Get entity type options.
    $entity_type_options = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $entity_type_options[$entity_type_id] = $entity_type->getLabel();
      }
    }

    $form['entity_type_container'] = [
      '#type' => 'container',
    ];
    $form['entity_type_container']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#default_value' => $this->configuration['entity_type'],
      '#options' => $entity_type_options,
      '#required' => TRUE,
      '#ajax' => $ajax,
    ];

    /* ********************************************************************** */
    // Bundles.
    /* ********************************************************************** */

    // Get entity type bundle options.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity_type */
    $entity_type = $this->entityTypeManager->getDefinition($this->configuration['entity_type']);
    $bundle_options = [];
    if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
      if ($bundles = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple()) {
        foreach ($bundles as $bundle_id => $bundle) {
          $bundle_options[$bundle_id] = $bundle->label();
        }
      }
    }
    if (empty($bundle_options)) {
      $bundle_options[$this->configuration['entity_type']] = $this->configuration['entity_type'];
      $access = FALSE;
    }
    else {
      $access = TRUE;
    }

    $form['bundle_container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'webform-test-ajax-container'],
    ];

    $this->configuration['bundle'] = isset($bundle_options[$this->configuration['bundle']]) ? $this->configuration['bundle'] : reset(array_keys($bundle_options));
    $form['bundle_container']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundles'),
      '#default_value' => $this->configuration['bundle'],
      '#options' => $bundle_options,
      '#ajax' => $ajax,
      '#access' => $access,
    ];

    /* ********************************************************************** */
    // Fields.
    /* ********************************************************************** */

    // Get elements options.
    $element_options = [];
    $elements = $this->webform->getElementsInitializedFlattenedAndHasValue();
    foreach ($elements as $element_key => $element) {
      $element_options[$element_key] = $element['#title'] ?? $element_key;
    }

    // Get field options.
    $fields = $this->entityFieldManager->getFieldDefinitions($this->configuration['entity_type'], $this->configuration['bundle']);
    $field_options = [];
    foreach ($fields as $field_name => $field) {
      $field_options[$field_name] = $field->getLabel();
    }

    $form['bundle_container']['fields'] = [
      '#type' => 'webform_mapping',
      '#title' => 'Fields',
      '#description' => $this->t('Please select which fields webform submission data should be mapped to'),
      '#description_display' => 'before',
      '#default_value' => $this->configuration['fields'],
      '#required' => TRUE,
      '#source' => $element_options,
      '#destination' => $field_options,
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['entity_type'] = $form_state->getValue('entity_type');
    $this->configuration['bundle'] = $form_state->getValue('bundle');
    $this->configuration['fields'] = $form_state->getValue('fields');
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array containing entity reference details element.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    return NestedArray::getValue($form, ['settings', 'bundle_container']);
  }

}
