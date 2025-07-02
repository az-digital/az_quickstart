<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\commerce_order\Event\OrderEvent;
use Drupal\commerce_order\Event\OrderEvents;
use Drupal\google_tag\EventCollectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Commerce order paid subscriber.
 */
final class CommerceOrderSubscriber implements EventSubscriberInterface {

  /**
   * The Event Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * CommerceOrderSubscriber constructor.
   *
   * @param \Drupal\google_tag\EventCollectorInterface $eventCollector
   *   Collector service.
   */
  public function __construct(EventCollectorInterface $eventCollector) {
    $this->eventCollector = $eventCollector;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      OrderEvents::ORDER_PAID => 'onPaid',
    ];
  }

  /**
   * Fires an event on successful paid order.
   *
   * @param \Drupal\commerce_order\Event\OrderEvent $event
   *   Event object.
   */
  public function onPaid(OrderEvent $event): void {
    $this->eventCollector->addEvent('commerce_purchase', [
      'order' => $event->getOrder(),
    ]);
  }

}
