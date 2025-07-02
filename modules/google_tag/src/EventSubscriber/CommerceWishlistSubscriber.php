<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\commerce_wishlist\Event\WishlistEntityAddEvent;
use Drupal\commerce_wishlist\Event\WishlistEvents;
use Drupal\google_tag\EventCollectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Commerce wishlist event subscriber.
 */
final class CommerceWishlistSubscriber implements EventSubscriberInterface {

  /**
   * The Event Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * CommerceWishlistSubscriber constructor.
   *
   * @param \Drupal\google_tag\EventCollectorInterface $eventCollector
   *   Collector.
   */
  public function __construct(EventCollectorInterface $eventCollector) {
    $this->eventCollector = $eventCollector;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      WishlistEvents::WISHLIST_ENTITY_ADD => 'onAdd',
    ];
  }

  /**
   * Fires wish list event on wishlist addition.
   *
   * @param \Drupal\commerce_wishlist\Event\WishlistEntityAddEvent $event
   *   Event object.
   */
  public function onAdd(WishlistEntityAddEvent $event): void {
    $this->eventCollector->addEvent(
      'commerce_add_to_wishlist',
      ['item' => $event->getWishlistItem()]
    );
  }

}
