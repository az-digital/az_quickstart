<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\UserDataInterface;
use Drupal\webform\Entity\Webform;

/**
 * Webform entity reference (field) manager.
 *
 * The webform entity reference (field) manager is used to track webforms that
 * are attached to entities, specifically webform nodes. Generally, only one
 * webform is attached to a single node. Field API does allow multiple
 * webforms to be attached to any entity and this services helps handle this
 * edge case.
 */
class WebformEntityReferenceManager implements WebformEntityReferenceManagerInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cache of source entity webforms.
   *
   * @var array
   */
  protected $webforms = [];

  /**
   * Cache of source entity field names.
   *
   * @var array
   */
  protected $fieldNames = [];

  /**
   * Constructs a WebformEntityReferenceManager object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for loading includes.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $route_match, AccountInterface $current_user, UserDataInterface $user_data, ModuleHandlerInterface $module_handler = NULL, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->moduleHandler = $module_handler ?: \Drupal::moduleHandler();
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::entityTypeManager();
  }

  /* ************************************************************************ */
  // User data methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function isUserWebformRoute(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $route_name = $this->routeMatch->getRouteName();
    $user_routes = [
      "entity.$entity_type.webform.test_form",
      "entity.$entity_type.webform.api_form",
    ];
    return in_array($this->routeMatch->getRouteName(), $user_routes)
      || (strpos($route_name, "entity.$entity_type.webform.results_") === 0)
      || (strpos($route_name, "entity.$entity_type.webform.share_") === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function setUserWebformId(EntityInterface $entity, $webform_id) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $uid = $this->currentUser->id();
    $name = $entity->id();

    $values = $this->userData->get($module, $uid, $name) ?: [];
    $values['target_id'] = $webform_id;

    $this->userData->set($module, $uid, $name, $values);

  }

  /**
   * {@inheritdoc}
   */
  public function getUserWebformId(EntityInterface $entity) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $uid = $this->currentUser->id();
    $name = $entity->id();

    $values = $this->userData->get($module, $uid, $name) ?: [];

    if (isset($values['target_id'])) {
      $webforms = $this->getWebforms($entity);
      if (isset($webforms[$values['target_id']])) {
        return $values['target_id'];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserWebformId(EntityInterface $entity) {
    $module = 'webform_' . $entity->getEntityTypeId();
    $name = $entity->id();

    $this->userData->delete($module, NULL, $name);
  }

  /* ************************************************************************ */
  // Field methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function hasField(EntityInterface $entity = NULL) {
    return $this->getFieldName($entity) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName(EntityInterface $entity = NULL) {
    $field_names = $this->getFieldNames($entity);
    return $field_names ? reset($field_names) : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldNames(EntityInterface $entity = NULL) {
    if ($entity === NULL || !$entity instanceof FieldableEntityInterface) {
      return [];
    }

    // Cache the source entity's field names.
    $entity_id = $entity->getEntityTypeId() . '-' . $entity->id();
    if (isset($this->fieldNames[$entity_id])) {
      return $this->fieldNames[$entity_id];
    }

    $field_names = [];
    if ($entity instanceof ContentEntityInterface) {
      $fields = $entity->getFieldDefinitions();
      foreach ($fields as $field_name => $field_definition) {
        if ($field_definition->getType() === 'webform') {
          $field_names[$field_name] = $field_name;
        }
      }
    }

    // Sort fields alphabetically.
    ksort($field_names);

    $this->fieldNames[$entity_id] = $field_names;
    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform(EntityInterface $entity = NULL) {
    if ($webform_id = $this->getUserWebformId($entity)) {
      return Webform::load($webform_id);
    }
    elseif ($webforms = $this->getWebforms($entity)) {
      return reset($webforms);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getWebforms(EntityInterface $entity = NULL) {
    // Cache the source entity's webforms.
    $entity_id = $entity->getEntityTypeId() . '-' . $entity->id();
    if (isset($this->webforms[$entity_id])) {
      return $this->webforms[$entity_id];
    }

    $target_entities = [];
    $sorted_entities = [];

    $field_names = $this->getFieldNames($entity);
    foreach ($field_names as $field_name) {
      foreach ($entity->$field_name as $item) {
        if ($item->entity) {
          $sorted_entities[$item->target_id] = (method_exists($item->entity, 'getWeight')) ? $item->entity->getWeight() : 0;
          $target_entities[$item->target_id] = $item->entity;
        }
      }
    }

    // Add paragraphs check.
    $this->getParagraphWebformsRecursive($entity, $target_entities, $sorted_entities);

    // Sort the webforms by key and then weight.
    ksort($sorted_entities);
    asort($sorted_entities, SORT_NUMERIC);

    // Return the sort webforms.
    $webforms = [];
    foreach (array_keys($sorted_entities) as $target_id) {
      $webforms[$target_id] = $target_entities[$target_id];
    }

    $this->webforms[$entity_id] = $webforms;

    return $webforms;
  }

  /* ************************************************************************ */
  // Paragraph methods.
  /* ************************************************************************ */

  /**
   * Get webform associate with a paragraph field from entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   * @param array $target_entities
   *   An associate array of targeted webform entities.
   * @param array $sorted_entities
   *   An associate array of sorted webform entities by weight.
   */
  protected function getParagraphWebformsRecursive(EntityInterface $entity, array &$target_entities, array &$sorted_entities) {
    // Add paragraphs check.
    if (!$this->moduleHandler->moduleExists('paragraphs')) {
      return;
    }
    // Make sure the entity exists and is fieldable.
    if ($entity === NULL || !$entity instanceof FieldableEntityInterface) {
      return;
    }

    $paragraph_fields = $this->getParagraphFieldNames($entity);
    foreach ($paragraph_fields as $paragraph_field) {
      if (!$entity->hasField($paragraph_field)) {
        continue;
      }

      foreach ($entity->get($paragraph_field) as $paragraph_item) {
        $paragraph = $paragraph_item->entity;
        if ($paragraph) {
          $webform_field_names = $this->getFieldNames($paragraph);
          foreach ($webform_field_names as $webform_field_name) {
            foreach ($paragraph->$webform_field_name as $webform_field_item) {
              if ($webform_field_item->entity) {
                $sorted_entities[$webform_field_item->target_id] = (method_exists($webform_field_item->entity, 'getWeight')) ? $webform_field_item->entity->getWeight() : 0;
                $target_entities[$webform_field_item->target_id] = $webform_field_item->entity;
              }
            }
          }
          $this->getParagraphWebformsRecursive($paragraph, $target_entities, $sorted_entities);
        }
      }
    }
  }

  /**
   * Get paragraph field names.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A fieldable content entity.
   *
   * @return array
   *   An array of paragraph field names.
   */
  protected function getParagraphFieldNames(EntityInterface $entity) {
    $fields = $this->entityTypeManager->getStorage('field_storage_config')->loadByProperties([
      'entity_type' => $entity->getEntityTypeId(),
      'type' => 'entity_reference_revisions',
    ]);

    $field_names = [];
    foreach ($fields as $field) {
      if ($field->getSetting('target_type') === 'paragraph') {
        $field_name = $field->get('field_name');
        $field_names[$field_name] = $field_name;
      }
    }
    return $field_names;
  }

  /* ************************************************************************ */
  // Table methods.
  /* ************************************************************************ */

  /**
   * {@inheritdoc}
   */
  public function getTableNames() {
    /** @var \Drupal\field\FieldStorageConfigInterface[] $field_storage_configs */
    $field_storage_configs = FieldStorageConfig::loadMultiple();
    $tables = [];
    foreach ($field_storage_configs as $field_storage_config) {
      if ($field_storage_config->getType() === 'webform') {
        $webform_field_table = $field_storage_config->getTargetEntityTypeId();
        $webform_field_name = $field_storage_config->getName();
        $tables[$webform_field_table . '__' . $webform_field_name] = $webform_field_name;
        $tables[$webform_field_table . '_revision__' . $webform_field_name] = $webform_field_name;
      }
    }
    return $tables;
  }

}
