<?php

namespace Drupal\metatag\TypedData;

use Drupal\Core\TypedData\ComputedItemListTrait as CoreComputedItemListTrait;

/**
 * Provides a computed item list with customizable caching.
 *
 * Unlike \Drupal\Core\TypedData\ComputedItemListTrait this doesn't just cache
 * the first computed value, but allows for it to be recomputed as necessary by
 * overriding ::valueNeedsRecomputing().
 *
 * It's important that ::computeValue() doesn't just append list items as it's
 * not guaranteed to be run only once.
 *
 * @see \Drupal\Core\TypedData\ComputedItemListTrait
 *
 * @todo Fold back into core if possible.
 */
trait ComputedItemListTrait {

  use CoreComputedItemListTrait;

  /**
   * Ensures that values are only computed once.
   */
  protected function ensureComputedValue() {
    // We guarantee not to run ::valueNeedsRecomputing() unless the value's
    // already been calculated.
    if ($this->valueComputed === FALSE || $this->valueNeedsRecomputing()) {
      $this->computeValue();
      $this->valueComputed = TRUE;
    }
  }

  /**
   * Returns whether the value should be recomputed.
   *
   * This is run after the value has been computed at least once.
   *
   * @return bool
   *   State need for recomputing value.
   */
  abstract protected function valueNeedsRecomputing();

}
