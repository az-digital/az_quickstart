<?php

namespace Drupal\webform;

/**
 * Provides an interface defining a webform conditions (#states) manager.
 */
interface WebformEntityConditionsManagerInterface {

  /**
   * Convert a webform's #states to a human read-able format.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $states
   *   An element's #states array.
   * @param array $options
   *   An associative array of configuration options.
   *
   * @return array
   *   A renderable array containing the webform's #states displayed in
   *   a human read-able format.
   */
  public function toText(WebformInterface $webform, array $states, array $options = []);

}
