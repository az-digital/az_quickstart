<?php

namespace Drupal\viewsreference\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a views reference setting item annotation object.
 *
 * @see \Drupal\viewsreference\Plugin\ViewsReferenceSettingManager
 * @see plugin_api
 *
 * @Annotation
 */
class ViewsReferenceSetting extends Plugin {

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
   * The field default value.
   *
   * @var mixed
   */
  public $default_value;

}
