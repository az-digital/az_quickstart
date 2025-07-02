<?php

declare(strict_types=1);

namespace Drupal\google_tag;

/**
 * Collector interface.
 */
interface EventCollectorInterface {

  /**
   * Adds event to the event list.
   *
   * @param string $name
   *   The event plugin ID.
   * @param array $contexts
   *   The contexts.
   *
   * @phpstan-param array<string, mixed> $contexts
   */
  public function addEvent(string $name, array $contexts = []): void;

  /**
   * Adds a delayed event.
   *
   * This pushes the event into the user's session to be bubbled on the next
   * page render. Used when tracking events caused by form submissions.
   *
   * @param string $name
   *   The event plugin ID.
   * @param array $contexts
   *   The contexts.
   *
   * @phpstan-param array<string, mixed> $contexts
   */
  public function addDelayedEvent(string $name, array $contexts = []): void;

  /**
   * Returns list of events.
   *
   * @return \Drupal\google_tag\Plugin\GoogleTag\Event\GoogleTagEventInterface[]
   *   Event list.
   */
  public function getEvents(): array;

}
