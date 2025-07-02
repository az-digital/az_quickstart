<?php

namespace Drupal\block_field;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines an interface for Block field selection plugins.
 */
interface BlockFieldSelectionInterface extends PluginInspectionInterface, PluginFormInterface {

  /**
   * Returns filtered block definitions based on plugin settings.
   *
   * @return array
   *   An array of filtered block definitions.
   */
  public function getReferenceableBlockDefinitions();

}
