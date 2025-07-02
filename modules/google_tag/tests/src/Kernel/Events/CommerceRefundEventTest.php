<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;

/**
 * Tests commerce refund event.
 *
 * @requires module commerce_order
 * @group google_tag
 */
final class CommerceRefundEventTest extends OrderKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['google_tag'];

  /**
   * Tests event fired for commerce refund.
   */
  public function testEvent(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'commerce_refund' => [],
      ],
    ])->save();

    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'completed',
    ]);
    $order->setOrderNumber('ORDER-1');
    $order_item = OrderItem::create([
      'title' => 'T-shirt',
      'type' => 'default',
      'quantity' => 2,
      'unit_price' => new Price('12.00', 'USD'),
    ]);
    $order_item->save();
    $order->addItem($order_item);
    $order->save();

    $collector = $this->container->get('google_tag.event_collector');
    $collector->addEvent('commerce_refund', [
      'order' => $order,
    ]);
    $events = $collector->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('refund', $events[0]->getName());
    self::assertEquals([
      'currency' => 'USD',
      'value' => '24.00',
      'transaction_id' => 'ORDER-1',
      'shipping' => '0',
      'tax' => '0',
      'items' => [],
    ], $events[0]->getData());
  }

}
