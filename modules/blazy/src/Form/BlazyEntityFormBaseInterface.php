<?php

namespace Drupal\blazy\Form;

/**
 * Defines re-usable utilities for blazy entity forms.
 */
interface BlazyEntityFormBaseInterface {

  /**
   * Returns the blazy admin service.
   */
  public function admin();

  /**
   * Returns the blazy manager service.
   */
  public function manager();

}
