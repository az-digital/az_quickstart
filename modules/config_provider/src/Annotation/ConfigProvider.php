<?php

namespace Drupal\config_provider\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Configuration provider item annotation object.
 *
 * @see \Drupal\config_provider\Plugin\ConfigProviderManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConfigProvider extends Plugin {


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

}
