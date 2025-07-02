<?php

namespace Drupal\webform\EntityStorage;

/**
 * Trait for webform entity storage management.
 *
 * Currently, in Core, Contrib, and Webform, there is no standard way to manage
 * calls to multiple entity storages in services and classes.
 *
 * The WebformEntityStorageTrait is only for services and classes.
 * Entity specific forms and controllers provide access to the
 * EntityTypeManager, and the existing APIs should continue to be used as
 * expected in the Webform module.
 *
 * @see https://www.drupal.org/project/drupal/issues/3162827
 *
 * @property \Drupal\Core\Entity\EntityStorageInterface $printEngineStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $fieldConfigStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $fieldStorageConfigStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $fileStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $nodeTypeStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $nodeStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $taxonomyTermStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $taxonomyVocabularyStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $userStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $userRoleStorage
 * @property \Drupal\Core\Entity\EntityStorageInterface $viewStorage
 * @property \Drupal\webform\WebformSubmissionStorageInterface $submissionStorage
 * @property \Drupal\webform\WebformEntityStorageInterface $webformStorage
 * @property \Drupal\webform\WebformSubmissionStorageInterface $webformSubmissionStorage
 * @property \Drupal\webform\WebformOptionsStorageInterface $webformOptionsStorage
 * @property \Drupal\webform_access\WebformAccessGroupStorageInterface $webformAccessGroupStorage
 * @property \Drupal\webform_access\WebformAccessTypeStorageInterface $webformAccessTypeStorage
 * @property \Drupal\webform_image_select\WebformImageSelectImagesStorageInterface $webformImageSelectImagesStorage
 * @property \Drupal\webform_options_custom\WebformOptionsCustomStorageInterface $webformOptionsCustomStorage
 */
trait WebformEntityStorageTrait {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * An associate array of entity type storage aliases.
   *
   * @var array
   */
  protected $entityStorageToTypeMappings = [
    // Default mappings.
    'printEngineStorage' => 'print_engine',
    'fieldConfigStorage' => 'field_config',
    'fieldStorageConfigStorage' => 'field_storage_config',
    'fileStorage' => 'file',
    'nodeTypeStorage' => 'node_type',
    'nodeStorage' => 'node',
    'taxonomyTermStorage' => 'taxonomy_term',
    'taxonomyVocabularyStorage' => 'taxonomy_vocabulary',
    'userStorage' => 'user',
    'userRoleStorage' => 'user_role',
    'viewStorage' => 'view',
    'webformStorage' => 'webform',
    'webformSubmissionStorage' => 'webform_submission',
    'webformOptionsStorage' => 'webform_options',
    'webformAccessGroupStorage' => 'webform_access_group',
    'webformAccessTypeStorage' => 'webform_access_type',
    'webformImageSelectImagesStorage' => 'webform_image_select_images',
    'webformOptionsCustomStorage' => 'webform_options_custom',
    // Custom mappings.
    'submissionStorage' => 'webform_submission',
  ];

  /**
   * Implements the magic __get() method.
   *
   * @param string $name
   *   The name of the variable to return.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Entity storage or NULL.
   */
  public function __get($name) {
    if (isset($this->entityStorageToTypeMappings[$name])) {
      $entity_type = $this->entityStorageToTypeMappings[$name];
      $class_name = get_class($this);
      // phpcs:ignore Drupal.Semantics.FunctionTriggerError.TriggerErrorTextLayoutRelaxed
      @trigger_error("$class_name::$name is deprecated in Webform 6.x and is removed from Webform 7.x Use \$this->entityTypeManager->getStorage('$entity_type') instead", E_USER_DEPRECATED);
      return $this->entityTypeManager->getStorage($entity_type);
    }
  }

  /**
   * Retrieves the entity storage.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage.
   */
  protected function getEntityStorage($entity_type) {
    return $this->entityTypeManager->getStorage($entity_type);
  }

  /**
   * Retrieves the webform storage.
   *
   * @return \Drupal\webform\WebformEntityStorageInterface
   *   The webform storage.
   */
  protected function getWebformStorage() {
    return $this->entityTypeManager->getStorage('webform');
  }

  /**
   * Retrieves the webform submission storage.
   *
   * @return \Drupal\webform\WebformSubmissionStorageInterface
   *   The webform submission storage.
   */
  protected function getSubmissionStorage() {
    return $this->entityTypeManager->getStorage('webform_submission');
  }

}
