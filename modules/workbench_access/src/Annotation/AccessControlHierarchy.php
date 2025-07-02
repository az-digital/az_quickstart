<?php

namespace Drupal\workbench_access\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a hierarchical access control annotation object.
 *
 * Plugin Namespace: Plugin\AccessControlHierarchy.
 *
 * For a working example, see
 * \Drupal\workbench_access\Plugin\AccessControlHierarchy\Taxonomy.
 *
 * Modules should use Drupal\workbench_access\AccessControlHierarchyBase as
 * a basis for new implementations.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AccessControlHierarchy extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The module required by the plugin.
   *
   * @var string
   */
  public $module;

  /**
   * The human-readable name of the hierarchy system.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The entity that defines an access control item. (Optional)
   *
   * @var string
   */
  public $entity;

  /**
   * A brief description of the hierarchy source.
   *
   * This will be shown when adding or configuring Workbench Access.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
