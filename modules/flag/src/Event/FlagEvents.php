<?php

namespace Drupal\flag\Event;

/**
 * Contains all events thrown in the Flag module.
 */
final class FlagEvents {

  /**
   * Event ID for when an entity is flagged.
   *
   * @Event
   *
   * @var string
   */
  const ENTITY_FLAGGED = 'flag.entity_flagged';

  /**
   * Event ID for when a previously flagged entity is unflagged.
   *
   * @Event
   *
   * @var string
   */
  const ENTITY_UNFLAGGED = 'flag.entity_unflagged';

}
