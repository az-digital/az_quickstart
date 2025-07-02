<?php

namespace Drupal\blazy\Plugin\views\style;

use Drupal\blazy\Views\BlazyStyleBaseInterface;

/**
 * Provides Blazy views style plugin interface.
 */
interface BlazyViewsInterface extends BlazyStyleBaseInterface {

  /**
   * Returns the blazy admin.
   */
  public function admin();

}
