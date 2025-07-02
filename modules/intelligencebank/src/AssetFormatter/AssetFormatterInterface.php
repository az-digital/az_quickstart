<?php

namespace Drupal\ib_dam\AssetFormatter;

use Drupal\ib_dam\Asset\AssetInterface;

/**
 * Interface AssetFormatterInterface.
 *
 * Describes asset's formatter behaviours.
 *
 * @package Drupal\ib_dam\AssetFormatter
 */
interface AssetFormatterInterface {

  /**
   * Format asset.
   *
   * @return mixed
   *   Returns whatever is ready for drupal render system.
   */
  public function format();

  /**
   * Get form elements used to get formatter settings.
   *
   * @return array
   *   The array with form elements.
   */
  public function settingsForm(AssetInterface $asset = NULL);

}
