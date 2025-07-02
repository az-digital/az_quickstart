<?php

namespace Drupal\workbench_access;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workbench_access\Entity\AccessSchemeInterface;
use Drupal\workbench_access\Plugin\views\filter\Section;

/**
 * Defines a base hierarchy class that others may extend.
 */
interface AccessControlHierarchyInterface extends ConfigurableInterface, DependentPluginInterface, PluginWithFormsInterface, PluginFormInterface {

  /**
   * Returns the id for a hierarchy.
   *
   * This id value is used throughout the code as the section id, which is
   * used to store information about access controls set by the module.
   *
   * @return string
   *   Access control ID.
   */
  public function id();

  /**
   * Returns the label for a hierarchy.
   *
   * @return string
   *   The human-readable label for a hierarchy.
   */
  public function label();

  /**
   * Gets the entity type id of entities controlled by the scheme.
   *
   * @return string
   *   The entity type id.
   */
  public function entityType();

  /**
   * Gets the entire hierarchy tree.
   *
   * This method will return a hierarchy tree from any supported source in a
   * standard array structure. Using this method allows our code to abstract
   * handling of access controls.
   *
   * The array has the following components.
   *
   *   id - The lookup id of the entity or object (e.g. term tid).
   *   parents - A sorted array of ids for any parent items of this item.
   *   label - The human-readable label of the entity or object.
   *   description - A human-readable help description of this item.
   *   path - A fully-formed URL string for this item.
   *   depth - The depth in the hierarchy of this item.
   *   weight - The sort order (weight) of this item at its depth.
   *
   * The first two items in this array (id, parents) are used to generate
   * access control logic. The remaining items are used for building forms
   * and user interfaces. Note that the last two items (depth, weight) are
   * normally handled by the sorting done by the tree builder. They are
   * provided in case your code needs to re-sort the tree.
   *
   * @return array
   *   An array in the format defined above.
   */
  public function getTree();

  /**
   * Resets the internal cache of the tree.
   *
   * This code is not currently used by the module. It is provided as a
   * convenience for developers since $tree is a protected property.
   */
  public function resetTree();

  /**
   * Loads a hierarchy definition for a single item in the tree.
   *
   * @param string $id
   *   The identifier for the item, such as a term id.
   *
   * @return \Drupal\workbench_access\AccessControlHierarchyInterface|null
   *   A plugin implementation.
   */
  public function load($id);

  /**
   * Check if this access scheme applies to the given entity.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   *
   * @return bool
   *   TRUE if this access scheme applies to the entity.
   */
  public function applies($entity_type_id, $bundle);

  /**
   * Responds to request for node access.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The node being checked. In future this may handle other entity types.
   * @param string $op
   *   The operation, e.g. update, delete.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user requesting access to the node.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   An access result response. By design, this is either ignore or deny.
   *
   * @see workbench_access_entity_access()
   */
  public function checkEntityAccess(AccessSchemeInterface $scheme, EntityInterface $entity, $op, AccountInterface $account);

  /**
   * Retrieves the access control values from an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A Drupal entity, typically a node or a user.
   *
   * @return array
   *   An simple array of section ids from the entity being checked.
   */
  public function getEntityValues(EntityInterface $entity);

  /**
   * Alters the selection options provided for an access control field.
   *
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme.
   * @param array $form
   *   The content entry form to alter.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Active form state data.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object that the form is modifying.
   */
  public function alterForm(AccessSchemeInterface $scheme, array &$form, FormStateInterface &$form_state, ContentEntityInterface $entity);

  /**
   * Gets any options that are set but cannot be changed by the editor.
   *
   * These options are typically passed as hidden form values so that an
   * editor does not remove sections that they cannot access. See submitEntity()
   * below for the implementation.
   *
   * @param array $field
   *   The field element from a node form, after running through alterOptions().
   *
   * @return array
   *   An array of section ids to remove from a form or list.
   */
  public function disallowedOptions(array $field);

  /**
   * Gets applicable fields for given entity type and bundle.
   *
   * Plugin implementations are responsible for declaring what fields on an
   * entity are used for access control.
   *
   * @param string $entity_type
   *   Entity type ID.
   * @param string $bundle
   *   Bundle ID.
   *
   * @return array
   *   Associative array of fields with keys entity_type, bundle and field.
   */
  public function getApplicableFields($entity_type, $bundle);

  /**
   * Responds to the submission of an entity form.
   *
   * If the entity contains section values that the user cannot change, they
   * are passed in the 'workbench_access_disallowed' field on the form. Plugins
   * should examine that value and make modifications to their target field
   * as necessary.
   *
   * A default implementation is provided which only supports nodes.
   *
   * @param array &$form
   *   A form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form_state object.
   */
  public static function submitEntity(array &$form, FormStateInterface $form_state);

  /**
   * Massage form values as appropriate during entity submit.
   *
   * This method is invoked by submitEntity() to save items passed by the
   * disallowedOptions() method.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being edited.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $hidden_values
   *   Hidden values passed by the form, generally from disallowedOptions().
   */
  public function massageFormValues(ContentEntityInterface $entity, FormStateInterface $form_state, array $hidden_values);

  /**
   * Adds views data for the plugin.
   *
   * @param array $data
   *   Views data.
   * @param \Drupal\workbench_access\Entity\AccessSchemeInterface $scheme
   *   Access scheme that wraps this plugin.
   */
  public function viewsData(array &$data, AccessSchemeInterface $scheme);

  /**
   * Returns information on how to join this section data to a base view table.
   *
   * @param string $entity_type
   *   The base table of the view.
   * @param string $key
   *   The primary key of the base table.
   * @param string $alias
   *   The views alias of the base table.
   *
   * @return array
   *   The configuration array for adding a views JOIN statement.
   */
  public function getViewsJoin($entity_type, $key, $alias = NULL);

  /**
   * Adds a where clause to a view when using a section filter.
   *
   * @param \Drupal\workbench_access\Plugin\views\filter\Section $filter
   *   The views filter object provided by Workbench Access.
   * @param array $values
   *   An array of values for the current view.
   */
  public function addWhere(Section $filter, array $values);

  /**
   * Informs the plugin that a dependency of the scheme will be deleted.
   *
   * @param array $dependencies
   *   An array of dependencies that will be deleted keyed by dependency type.
   *
   * @return bool
   *   TRUE if the workflow settings have been changed, FALSE if not.
   *
   * @see \Drupal\Core\Config\ConfigEntityInterface::onDependencyRemoval()
   *
   * @todo https://www.drupal.org/node/2579743 make part of a generic interface.
   */
  public function onDependencyRemoval(array $dependencies);

}
