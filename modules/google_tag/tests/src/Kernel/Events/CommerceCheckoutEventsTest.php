<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_price\Price;
use Drupal\Core\Url;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\commerce_order\Kernel\OrderKernelTestBase;
use Drupal\Tests\google_tag\Kernel\AssertGoogleTagTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Commerce checkout event test.
 *
 * @requires module commerce_checkout
 * @group google_tag
 */
final class CommerceCheckoutEventsTest extends OrderKernelTestBase {

  use AssertGoogleTagTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'google_tag',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig('commerce_checkout');
  }

  /**
   * Tests checkout begin.
   */
  public function testBeginCheckout(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'commerce_begin_checkout' => [],
      ],
    ])->save();

    $user = $this->createUser();
    $cart = $this->container->get('commerce_cart.cart_provider')->createCart(
      'default',
      $this->store,
      $user
    );
    $this->container->get('commerce_cart.cart_manager')->addOrderItem(
      $cart,
      OrderItem::create([
        'title' => 'T-shirt',
        'type' => 'default',
        'quantity' => 2,
        'unit_price' => new Price('12.00', 'USD'),
      ])
    );
    $this->container->get('current_user')->setAccount($user);

    $request = Request::create(
      Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $cart->id(),
      ])->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    $response = $this->doRequest(
      Request::create($response->headers->get('Location'))
    );
    self::assertEquals(200, $response->getStatusCode());
    $this->assertGoogleTagEvents([
      [
        'name' => 'begin_checkout',
        'data' => [
          'currency' => 'USD',
          'value' => '24',
          'items' => [
            [
              'item_id' => '',
              'item_name' => 'T-shirt',
              'affiliation' => 'Default store',
              'discount' => '0',
              'price' => '12',
              'quantity' => '2',
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * Tests purchase.
   */
  public function testPurchase(): void {
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'commerce_purchase' => [],
      ],
    ])->save();

    $user = $this->createUser();
    $cart = $this->container->get('commerce_cart.cart_provider')->createCart(
      'default',
      $this->store,
      $user
    );
    $this->container->get('commerce_cart.cart_manager')->addOrderItem(
      $cart,
      OrderItem::create([
        'title' => 'T-shirt',
        'type' => 'default',
        'quantity' => 2,
        'unit_price' => new Price('12.00', 'USD'),
      ])
    );
    $cart->getState()->applyTransitionById('place');
    $cart->save();
    $this->container->get('current_user')->setAccount($user);

    $request = Request::create(
      Url::fromRoute('commerce_checkout.form', [
        'commerce_order' => $cart->id(),
      ])->toString()
    );
    $response = $this->doRequest($request);
    self::assertEquals(302, $response->getStatusCode());
    $response = $this->doRequest(
      Request::create($response->headers->get('Location'))
    );
    self::assertEquals(200, $response->getStatusCode());
    $this->assertGoogleTagEvents([
      [
        'name' => 'purchase',
        'data' => [
          'currency' => 'USD',
          'value' => '24',
          'transaction_id' => $cart->getOrderNumber(),
          'shipping' => '0',
          'tax' => '0',
          'items' => [
            [
              'item_id' => '',
              'item_name' => 'T-shirt',
              'affiliation' => 'Default store',
              'discount' => '0',
              'price' => '12',
              'quantity' => '2',
            ],
          ],
        ],
      ],
    ]);
  }

  /**
   * Sends request and sets raw content.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Symfony request.
   *
   * @throws \Exception
   */
  protected function doRequest(Request $request): Response {
    $response = $this->container->get('http_kernel')->handle($request);
    $content = $response->getContent();
    self::assertNotFalse($content);
    $this->setRawContent($content);
    return $response;
  }

}
