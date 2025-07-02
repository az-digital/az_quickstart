<?php

namespace Drupal\blazy\Views;

/**
 * Provides base vanilla views style plugin interface.
 */
interface BlazyStyleVanillaInterface {

  /**
   * Returns the blazy manager.
   *
   * @todo remove after tests at 3.x.
   */
  public function blazyManager();

  /**
   * Returns the string values for the expected Title, ET label, List, Term.
   *
   * @todo re-check this, or if any consistent way to retrieve string values.
   */
  public function getFieldString($row, $name, $index, $clean = TRUE): array;

}
