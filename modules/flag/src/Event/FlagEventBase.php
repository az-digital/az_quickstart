<?php

namespace Drupal\flag\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\flag\FlagInterface;

/**
 * Base Event from which other flag event are defined.
 */
abstract class FlagEventBase extends Event {

  /**
   * The Flag in question.
   *
   * @var \Drupal\flag\FlagInterface
   */
  protected $flag;

  /**
   * Build the flag event class.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag to be acted upon.
   */
  public function __construct(FlagInterface $flag) {
    $this->flag = $flag;
  }

  /**
   * Get the flag entity related to the event.
   *
   * @return \Drupal\flag\FlagInterface
   *   The flag related to the event.
   */
  public function getFlag() {
    return $this->flag;
  }

}
