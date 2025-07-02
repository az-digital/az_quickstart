<?php

namespace Drupal\inline_entity_form\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\inline_entity_form\InlineFormInterface;
use Drupal\rat\v1\RenderArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generic entity inline form handler.
 */
class EntityInlineForm implements InlineFormInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type managed by this handler.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs the inline entity form controller.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, EntityTypeInterface $entity_type, ThemeManagerInterface $theme_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->entityType = $entity_type;
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $entity_type,
      $container->get('theme.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    return [
      'singular' => $this->entityType->getSingularLabel(),
      'plural' => $this->entityType->getPluralLabel(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityLabel(EntityInterface $entity) {
    return $entity->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $definitions = $this->entityFieldManager->getBaseFieldDefinitions($this->entityType->id());
    $label_key = $this->entityType->getKey('label');
    $label_field_label = $this->t('Label');
    if ($label_key && isset($definitions[$label_key])) {
      $label_field_label = $definitions[$label_key]->getLabel();
    }
    $bundle_key = $this->entityType->getKey('bundle');
    $bundle_field_label = $this->t('Type');
    if ($bundle_key && isset($definitions[$bundle_key])) {
      $bundle_field_label = $definitions[$bundle_key]->getLabel();
    }

    $fields = [];
    $fields['label'] = [
      'type' => 'label',
      'label' => $label_field_label,
      'weight' => 1,
    ];
    if (count($bundles) > 1) {
      $fields[$bundle_key] = [
        'type' => 'field',
        'label' => $bundle_field_label,
        'weight' => 2,
        'display_options' => [
          'type' => 'entity_reference_label',
          'settings' => ['link' => FALSE],
        ],
      ];
    }

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isTableDragEnabled($element) {
    $children = Element::children($element);
    // If there is only one row, disable tabledrag.
    if (count($children) == 1) {
      return FALSE;
    }
    // If one of the rows is in form context, disable tabledrag.
    foreach ($children as $key) {
      if (!empty($element[$key]['form'])) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(array $entity_form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];
    $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
    $form_display->buildForm($entity, $entity_form, $form_state);
    $entity_form['#ief_element_submit'][] = [
      get_class($this),
      'submitCleanFormState',
    ];
    // Inline entities inherit the parent language.
    $langcode_key = $this->entityType->getKey('langcode');
    if ($langcode_key && isset($entity_form[$langcode_key])) {
      // Safely restrict access. Entity cacheability already set.
      RenderArray::alter($entity_form[$langcode_key])->restrictAccess(FALSE, NULL);
    }
    if (!empty($entity_form['#translating'])) {
      // Hide the non-translatable fields.
      foreach ($entity->getFieldDefinitions() as $field_name => $definition) {
        if (isset($entity_form[$field_name]) && $field_name != $langcode_key) {
          // Safely restrict access. Field definition cacheability already set.
          RenderArray::alter($entity_form[$field_name])->restrictAccess($definition->isTranslatable(), NULL);
        }
      }
    }

    // Hide the log message field for revisionable entity types. It cannot be
    // disabled in UI and does not make sense in inline entity form context.
    if (($this->entityType instanceof ContentEntityTypeInterface)) {
      if ($log_message_key = $this->entityType->getRevisionMetadataKey('revision_log_message')) {
        // Safely restrict access. Entity type cacheability already set.
        RenderArray::alter($entity_form[$log_message_key])->restrictAccess(FALSE, NULL);
      }
    }

    // Determine the children of the entity form before it has been altered.
    $children_before = Element::children($entity_form);

    // Allow other modules and themes to alter the form.
    $this->moduleHandler->alter('inline_entity_form_entity_form', $entity_form, $form_state);
    $this->themeManager->alter('inline_entity_form_entity_form', $entity_form, $form_state);

    // Determine the children of the entity form after it has been altered.
    $children_after = Element::children($entity_form);

    // Ensure that any new children added have #tree, #parents, #array_parents
    // and handle setting the proper #group if it's referencing a local element.
    // Note: the #tree, #parents and #array_parents code is a direct copy from
    // \Drupal\Core\Form\FormBuilder::doBuildForm.
    $children_diff = array_diff($children_after, $children_before);
    foreach ($children_diff as $child) {
      // Don't squash an existing tree value.
      if (!isset($entity_form[$child]['#tree'])) {
        $entity_form[$child]['#tree'] = $entity_form['#tree'];
      }

      // Don't squash existing parents value.
      if (!isset($entity_form[$child]['#parents'])) {
        // Check to see if a tree of child elements is present. If so,
        // continue down the tree if required.
        $entity_form[$child]['#parents'] = $entity_form[$child]['#tree'] && $entity_form['#tree'] ? array_merge($entity_form['#parents'], [$child]) : [$child];
      }

      // Ensure #array_parents follows the actual form structure.
      $array_parents = $entity_form['#array_parents'];
      $array_parents[] = $child;
      $entity_form[$child]['#array_parents'] = $array_parents;

      // Detect if there is a #group and it specifies a local element. If so,
      // change it to use the proper local element's #parents group name.
      if (isset($entity_form[$child]['#group']) && isset($entity_form[$entity_form[$child]['#group']])) {
        $entity_form[$child]['#group'] = implode('][', $entity_form[$entity_form[$child]['#group']]['#parents']);
      }
    }
    $entity_form['#attached']['library'][] = 'core/drupal.form';

    return $entity_form;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormValidate(array &$entity_form, FormStateInterface $form_state) {
    // Perform entity validation only if the inline form was submitted,
    // skipping other requests such as file uploads.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#ief_submit_trigger'])) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $entity_form['#entity'];
      $this->buildEntity($entity_form, $entity, $form_state);
      $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
      $form_display->validateFormValues($entity, $entity_form, $form_state);
      $entity->setValidationRequired(FALSE);

      foreach ($form_state->getErrors() as $message) {
        // $name may be unknown in $form_state and
        // $form_state->setErrorByName($name, $message) may suppress the error
        // message.
        $form_state->setError($triggering_element, $message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormSubmit(array &$entity_form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $entity_form['#entity'];
    $this->buildEntity($entity_form, $entity, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function delete($ids, $context) {
    $storage_handler = $this->entityTypeManager->getStorage($this->entityType->id());
    $entities = $storage_handler->loadMultiple($ids);
    $storage_handler->delete($entities);
  }

  /**
   * Builds an updated entity object based upon the submitted form values.
   *
   * @param array $entity_form
   *   The entity form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function buildEntity(array $entity_form, ContentEntityInterface $entity, FormStateInterface $form_state) {
    $form_display = $this->getFormDisplay($entity, $entity_form['#form_mode']);
    $form_display->extractFormValues($entity, $entity_form, $form_state);
    // Invoke all specified builders for copying form values to entity fields.
    if (isset($entity_form['#entity_builders'])) {
      foreach ($entity_form['#entity_builders'] as $function) {
        call_user_func_array(
          $function,
          [$entity->getEntityTypeId(), $entity, &$entity_form, &$form_state]
        );
      }
    }
  }

  /**
   * Cleans up the form state for a submitted entity form.
   *
   * After field_attach_submit() has run and the form has been closed, the form
   * state still contains field data in $form_state->get('field'). Unless that
   * data is removed, the next form with the same #parents (reopened add
   * form, for example) will contain data (i.e. uploaded files) from the
   * previous form.
   *
   * @param array $entity_form
   *   The entity form.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  public static function submitCleanFormState(array &$entity_form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $entity_form['#entity'];
    $bundle = $entity->bundle();
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $instances */
    $instances = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_form['#entity_type'], $bundle);
    foreach ($instances as $instance) {
      $field_name = $instance->getName();
      if (!empty($entity_form[$field_name]['#parents'])) {
        $parents = $entity_form[$field_name]['#parents'];
        array_pop($parents);
        if (!empty($parents)) {
          $field_state = [];
          WidgetBase::setWidgetState($parents, $field_name, $form_state, $field_state);
        }
      }
    }
  }

  /**
   * Gets the form display for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $form_mode
   *   The form mode.
   *
   * @return \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   *   The form display.
   */
  protected function getFormDisplay(ContentEntityInterface $entity, $form_mode) {
    return EntityFormDisplay::collectRenderDisplay($entity, $form_mode);
  }

}
