<?php

namespace Drupal\ib_dam\Asset;

/**
 * Interface EmbedAssetInterface.
 *
 * Describes features of embed assets.
 *
 * @package Drupal\ib_dam\Asset
 */
interface EmbedAssetInterface {

  /**
   * Returns asset remote url.
   *
   * @return string
   *   The asset absolute url.
   */
  public function getUrl();

  /**
   * Setter for asset url property.
   *
   * @param string $url
   *   The asset absolute url.
   *
   * @return \Drupal\ib_dam\Asset\EmbedAssetInterface
   *   Return this.
   */
  public function setUrl($url);

  /**
   * Returns asset extra settings.
   * Used to store additional settings like width, height, etc.
   *
   * @return array
   *   The asset extra settings.
   */
  public function getDisplaySettings();

  /**
   * Setter for asset extra settings property.
   *
   * @param array $settings
   *   The asset extra settings.
   *
   * @return \Drupal\ib_dam\Asset\EmbedAssetInterface
   *   Return this.
   */
  public function setDisplaySettings(array $settings = []);

}
