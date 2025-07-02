<?php

namespace Drupal\block_field;

/**
 * Provides an interface defining a BLock field manager.
 */
interface BlockFieldManagerInterface {

  /**
   * Get sorted listed of supported block definitions.
   *
   * @return array
   *   An associative array of supported block definitions.
   */
  public function getBlockDefinitions();

  /**
   * Get list of all block categories.
   *
   * @return string[]
   *   A numerically indexed array of block categories.
   */
  public function getBlockCategories();

}
