<?php

namespace Drupal\config_split\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Configuration Split setting entity.
 *
 * @ConfigEntityType(
 *   id = "config_split",
 *   label = @Translation("Configuration Split setting"),
 *   handlers = {
 *     "view_builder" = "Drupal\config_split\ConfigSplitEntityViewBuilder",
 *     "list_builder" = "Drupal\config_split\ConfigSplitEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\config_split\Form\ConfigSplitEntityForm",
 *       "edit" = "Drupal\config_split\Form\ConfigSplitEntityForm",
 *       "delete" = "Drupal\config_split\Form\ConfigSplitEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\config_split\ConfigSplitEntityHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "config_split",
 *   admin_permission = "administer configuration split",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/configuration/config-split/{config_split}",
 *     "add-form" = "/admin/config/development/configuration/config-split/add",
 *     "edit-form" = "/admin/config/development/configuration/config-split/{config_split}/edit",
 *     "delete-form" = "/admin/config/development/configuration/config-split/{config_split}/delete",
 *     "enable" = "/admin/config/development/configuration/config-split/{config_split}/enable",
 *     "disable" = "/admin/config/development/configuration/config-split/{config_split}/disable",
 *     "activate" = "/admin/config/development/configuration/config-split/{config_split}/activate",
 *     "deactivate" = "/admin/config/development/configuration/config-split/{config_split}/deactivate",
 *     "import" = "/admin/config/development/configuration/config-split/{config_split}/import",
 *     "export" = "/admin/config/development/configuration/config-split/{config_split}/export",
 *     "collection" = "/admin/config/development/configuration/config-split"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "weight",
 *     "status",
 *     "stackable",
 *     "storage",
 *     "folder",
 *     "module",
 *     "theme",
 *     "complete_list",
 *     "partial_list",
 *     "no_patching",
 *   }
 * )
 */
class ConfigSplitEntity extends ConfigEntityBase implements ConfigSplitEntityInterface {

  /**
   * The Configuration Split setting ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Configuration Split setting label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Configuration Split setting description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The weight of the configuration for sorting.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The status, whether to be used by default.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * The stackable property.
   *
   * @var bool
   */
  protected $stackable = FALSE;

  /**
   * The split storage.
   *
   * @var string
   */
  protected $storage;

  /**
   * The folder to export to.
   *
   * @var string
   */
  protected $folder = '';

  /**
   * The modules to split.
   *
   * @var array
   */
  protected $module = [];

  /**
   * The themes to split.
   *
   * @var array
   */
  protected $theme = [];

  /**
   * The configuration to explicitly filter out.
   *
   * @var string[]
   */
  protected $complete_list = [];

  /**
   * The configuration to partially split.
   *
   * @var string[]
   */
  protected $partial_list = [];

  /**
   * The configuration to not patch dependents.
   *
   * @var bool
   */
  protected $no_patching = FALSE;

}
