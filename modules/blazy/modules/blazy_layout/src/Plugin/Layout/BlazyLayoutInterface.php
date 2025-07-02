<?php

namespace Drupal\blazy_layout\Plugin\Layout;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides an interface for BlazyLayout methods.
 */
interface BlazyLayoutInterface extends ContainerFactoryPluginInterface {

  /**
   * Returns the region configurations based on the key.
   *
   * @param string $name
   *   The region name.
   * @param string $key
   *   The settings key.
   *
   * @return string
   *   The region settings value.
   */
  public function getRegionConfig($name, $key): string;

  /**
   * Sets the region configurations based on the key.
   *
   * @param string $name
   *   The region name.
   * @param array $values
   *   The settings values.
   *
   * @return $this
   *   The region settings value.
   */
  public function setRegionConfig($name, array $values): self;

}
