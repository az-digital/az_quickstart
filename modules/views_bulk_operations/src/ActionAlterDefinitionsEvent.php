<?php

namespace Drupal\views_bulk_operations;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines action alter definitions event.
 */
class ActionAlterDefinitionsEvent extends Event {

  /**
   * Array of action definitions.
   *
   * @var mixed[]
   */
  public array $definitions;

  /**
   * Additional parameters passed to alter event.
   *
   * @var mixed[]
   */
  public array $alterParameters;

}
