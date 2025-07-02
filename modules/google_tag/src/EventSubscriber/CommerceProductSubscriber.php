<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\commerce_product\Event\ProductEvents;
use Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent;
use Drupal\google_tag\EventCollectorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Commerce Product on variation change subscriber.
 */
final class CommerceProductSubscriber implements EventSubscriberInterface {

  /**
   * The Event Collector.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $eventCollector;

  /**
   * CommerceProductSubscriber constructor.
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
      ProductEvents::PRODUCT_VARIATION_AJAX_CHANGE => ['onVariationChange'],
    ];
  }

  /**
   * Fires an event on variation change.
   *
   * @param \Drupal\commerce_product\Event\ProductVariationAjaxChangeEvent $event
   *   Event object.
   */
  public function onVariationChange(ProductVariationAjaxChangeEvent $event): void {
    $this->eventCollector->addEvent('commerce_view_item', [
      'item' => $event->getProductVariation(),
    ]);
  }

}
