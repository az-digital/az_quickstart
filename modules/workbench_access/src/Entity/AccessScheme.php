<?php

namespace Drupal\workbench_access\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the Access scheme entity.
 *
 * @ConfigEntityType(
 *   id = "access_scheme",
 *   label = @Translation("Access scheme"),
 *   label_collection = @Translation("Access schemes"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\workbench_access\AccessSchemeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\workbench_access\Form\AccessSchemeAddForm",
 *       "edit" = "Drupal\workbench_access\Form\AccessSchemeForm",
 *       "delete" = "Drupal\workbench_access\Form\AccessSchemeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\workbench_access\Routing\AccessSchemeRouteProvider",
 *     },
 *   },
 *   config_prefix = "access_scheme",
 *   admin_permission = "administer workbench access",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/workflow/workbench_access/access_scheme/{access_scheme}",
 *     "add-form" = "/admin/config/workflow/workbench_access/access_scheme/add",
 *     "edit-form" = "/admin/config/workflow/workbench_access/access_scheme/{access_scheme}/edit",
 *     "delete-form" = "/admin/config/workflow/workbench_access/access_scheme/{access_scheme}/delete",
 *     "collection" = "/admin/config/workflow/workbench_access",
 *     "sections" = "/admin/config/workflow/workbench_access/{access_scheme}/sections",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "plural_label",
 *     "scheme",
 *     "scheme_settings",
 *   }
 * )
 */
class AccessScheme extends ConfigEntityBase implements AccessSchemeInterface, EntityWithPluginCollectionInterface {

  /**
   * The Access scheme ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Access scheme label.
   *
   * @var string
   */
  protected $label;

  /**
   * The access scheme plural label.
   *
   * @var string
   */
  protected $plural_label;

  /**
   * Access scheme id.
   *
   * @var string
   */
  protected $scheme;

  /**
   * Access scheme settings.
   *
   * @var array
   */
  protected $scheme_settings = [];

  /**
   * Plugin collection.
   *
   * @var \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   */
  protected $accessSchemePluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel() {
    return $this->plural_label;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessScheme() {
    return $this->getPluginCollection()->get($this->scheme);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'scheme_settings' => $this->getPluginCollection(),
    ];
  }

  /**
   * Encapsulates the creation of the access scheme plugin collection.
   *
   * @return \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
   *   The access scheme's plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->accessSchemePluginCollection && $this->scheme) {
      $this->accessSchemePluginCollection = new DefaultSingleLazyPluginCollection(\Drupal::service('plugin.manager.workbench_access.scheme'), $this->scheme, $this->scheme_settings);
    }
    return $this->accessSchemePluginCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('plugin.manager.entity_reference_selection')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    // Give the parent method and the access scheme type plugin a chance to
    // react to removed dependencies and report if either of these two made a
    // change.
    $parent_changed_entity = parent::onDependencyRemoval($dependencies);
    $plugin_changed_entity = $this->getAccessScheme()->onDependencyRemoval($dependencies);
    return $plugin_changed_entity || $parent_changed_entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    // Delete all associated storage.
    $section_storage = \Drupal::entityTypeManager()->getStorage('section_association');
    foreach ($entities as $entity) {
      $sections = $section_storage->loadByProperties([
        'access_scheme' => $entity->id(),
      ]);
      $section_storage->delete($sections);
      \Drupal::service('workbench_access.user_section_storage')->resetCache($entity);
    }
  }

}
