<?php

declare(strict_types=1);

namespace Drupal\az_event_trellis;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a trellis event import entity type.
 */
interface AZRecurringImportRuleInterface extends ConfigEntityInterface {

  /**
   * Returns a list of event ids associated with this import.
   *
   * @return array
   *   An array of Trellis event ids.
   */
  public function getEventIds();

}
