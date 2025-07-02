<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\commerce_cart\Event\CartEvents;
use Drupal\commerce_cart\Event\CartOrderItemAddEvent;
use Drupal\commerce_cart\Event\CartOrderItemRemoveEvent;
use Drupal\google_tag\EventCollectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Fires events on item addition/removal to/from cart.
 */
final class CommerceCartSubscriber implements EventSubscriberInterface {

  /**
   * The Event Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * CommerceCartSubscriber constructor.
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
      CartEvents::CART_ORDER_ITEM_ADD => 'onAdd',
      CartEvents::CART_ORDER_ITEM_REMOVE => 'onRemove',
    ];
  }

  /**
   * Fires event on item addition in cart.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemAddEvent $cart_event
   *   Event object.
   */
  public function onAdd(CartOrderItemAddEvent $cart_event): void {
    $this->eventCollector->addDelayedEvent(
      'commerce_add_to_cart',
      ['item' => $cart_event->getOrderItem()]
    );
  }

  /**
   * Fires event on item deletion from cart.
   *
   * @param \Drupal\commerce_cart\Event\CartOrderItemRemoveEvent $cart_event
   *   Event object.
   */
  public function onRemove(CartOrderItemRemoveEvent $cart_event): void {
    $this->eventCollector->addDelayedEvent(
      'commerce_remove_from_cart',
      ['item' => $cart_event->getOrderItem()]
    );
  }

}
