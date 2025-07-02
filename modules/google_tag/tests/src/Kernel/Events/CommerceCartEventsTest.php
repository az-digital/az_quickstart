<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\commerce_cart\Kernel\CartKernelTestBase;

/**
 * Commerce carts add/removal events test.
 *
 * @requires module commerce_cart
 * @group google_tag
 */
final class CommerceCartEventsTest extends CartKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['google_tag'];

  /**
   * Tests cart removal/addition events.
   */
  public function testEvents(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'commerce_add_to_cart' => [],
        'commerce_remove_from_cart' => [],
      ],
    ])->save();

    $product = Product::create([
      'type' => 'default',
      'title' => $this->randomString(),
    ]);
    $product->save();

    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'product_id' => $product->id(),
      'price' => new Price('12.00', 'USD'),
      'status' => 1,
    ]);
    $variation->save();

    $cart = $this->cartProvider->createCart('default', $this->store);
    $order_item = $this->cartManager->addEntity($cart, $variation);

    $events = $this->container->get('google_tag.event_collector')->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('add_to_cart', $events[0]->getName());
    self::assertEquals([
      'currency' => 'USD',
      'value' => '12.00',
      'items' => [
        [
          'item_name' => $variation->getOrderItemTitle(),
          'affiliation' => $this->store->label(),
          'discount' => '0',
          'price' => '12.00',
          'quantity' => '1',
          'item_id' => $variation->getSku(),
        ],
      ],
    ], $events[0]->getData());

    $this->cartManager->removeOrderItem($cart, $order_item);

    $events = $this->container->get('google_tag.event_collector')->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('remove_from_cart', $events[0]->getName());
    self::assertEquals([
      'currency' => 'USD',
      'value' => '12.00',
      'items' => [
        [
          'item_name' => $variation->getOrderItemTitle(),
          'affiliation' => $this->store->label(),
          'discount' => '0',
          'price' => '12.00',
          'quantity' => '1',
          'item_id' => $variation->getSku(),
        ],
      ],
    ], $events[0]->getData());
  }

}
