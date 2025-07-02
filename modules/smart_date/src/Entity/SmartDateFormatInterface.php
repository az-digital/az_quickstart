<?php

namespace Drupal\smart_date\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a smart date format.
 */
interface SmartDateFormatInterface extends ConfigEntityInterface {

  /**
   * Gets the array of options for this format.
   *
   * @return array
   *   The array of values used to assemble the output.
   */
  public function getOptions();

  /**
   * Sets the array of options for this format.
   *
   * @param array $options
   *   The array of options to use for this format.
   *
   * @return $this
   */
  public function setOptions(array $options);

}
