<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event for when a flagging is deleted.
 */
class UnflaggingEvent extends Event {

  /**
   * An array of flaggings.
   *
   * @var \Drupal\flag\FlaggingInterface[]
   */
  protected $flaggings = [];

  /**
   * Builds a new UnflaggingEvent.
   *
   * @param \Drupal\flag\FlaggingInterface[] $flaggings
   *   The flaggings.
   */
  public function __construct(array $flaggings) {
    $this->flaggings = $flaggings;
  }

  /**
   * Returns the flagging associated with the Event.
   *
   * @return \Drupal\flag\FlaggingInterface[]
   *   The flaggings.
   */
  public function getFlaggings() {
    return $this->flaggings;
  }

}
