<?php

namespace Drupal\config_filter\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Config filter plugin item annotation object.
 *
 * @see \Drupal\config_filter\Plugin\ConfigFilterPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConfigFilter extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * The plugin weight.
   *
   * The higher the weight the later in the filter order the plugin will be.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The status of the plugin.
   *
   * This is an easy way to turn off plugins.
   *
   * @var bool
   */
  public $status = TRUE;

  /**
   * The storages the plugin filters on.
   *
   * If it is left empty ['config.storage.sync'] will be assumed.
   * The only storage which is currently filtered is 'config.storage.sync'.
   *
   * @var string[]
   */
  public $storages = [];

}
