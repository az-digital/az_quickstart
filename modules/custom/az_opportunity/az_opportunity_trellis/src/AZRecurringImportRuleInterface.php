<?php

declare(strict_types=1);

namespace Drupal\az_opportunity_trellis;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a trellis opportunity import entity type.
 */
interface AZRecurringImportRuleInterface extends ConfigEntityInterface {

  /**
   * Returns a list of opportunity ids associated with this import.
   *
   * @return array
   *   An array of Trellis opportunity ids.
   */
  public function getOpportunityIds();

  /**
   * Returns a list of API queries parameters associated with this import.
   *
   * @return array
   *   An array of query parameters, keyed by parameter name.
   */
  public function getQueryParameters();

}
