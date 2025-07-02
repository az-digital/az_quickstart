<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\flag\FlaggingInterface;

/**
 * Event for when a flagging is created.
 */
class FlaggingEvent extends Event {

  /**
   * The flagging in question.
   *
   * @var \Drupal\flag\FlaggingInterface
   */
  protected $flagging;

  /**
   * Builds a new FlaggingEvent.
   *
   * @param \Drupal\flag\FlaggingInterface $flagging
   *   The flagging.
   */
  public function __construct(FlaggingInterface $flagging) {
    $this->flagging = $flagging;
  }

  /**
   * Returns the flagging associated with the Event.
   *
   * @return \Drupal\flag\FlaggingInterface
   *   The flagging.
   */
  public function getFlagging() {
    return $this->flagging;
  }

}
