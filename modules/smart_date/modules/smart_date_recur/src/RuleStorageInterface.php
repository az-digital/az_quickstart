<?php

namespace Drupal\smart_date_recur;

use Drupal\Core\Entity\ContentEntityStorageInterface;

/**
 * Defines an interface for Smart Date recur rule entity storage classes.
 */
interface RuleStorageInterface extends ContentEntityStorageInterface {

  /**
   * Returns the fids of feeds that need to be refreshed.
   *
   * @return array
   *   A list of feed ids to be refreshed.
   */
  public function getRuleIdsToCheck();

}
