<?php

namespace Drupal\block_field\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Block field selection item annotation object.
 *
 * @see \Drupal\block_field\BlockFieldSelectionManager
 * @see plugin_api
 *
 * @Annotation
 */
class BlockFieldSelection extends Plugin {

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
