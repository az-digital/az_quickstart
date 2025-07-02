<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Core\Session\AccountInterface;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\Tests\google_tag\Kernel\AssertGoogleTagTrait;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Commerce product events test.
 *
 * @group google_tag
 */
final class CommerceProductEventsTest extends CommerceKernelTestBase {

  use AssertGoogleTagTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'commerce_product',
    'google_tag',
    'google_tag_test',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user', 'commerce_product']);
    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    user_role_grant_permissions(AccountInterface::ANONYMOUS_ROLE, ['view commerce_product']);
  }

  /**
   * Test view item list event.
   */
  public function testViewItemList(): void {
    $this->container->get('current_user')->setAccount(User::getAnonymousUser());
    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => [
        'commerce_view_item_list' => [],
      ],
    ])->save();
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'title' => $this->randomString(),
      'price' => new Price('12.00', 'USD'),
      'status' => 1,
    ]);
    $variation->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'FooBaz Widget',
      'stores' => [$this->store],
      'variations' => [$variation],
      'status' => 1,
    ]);
    $product->save();

    $request = Request::create('/catalog');
    $response = $this->container->get('http_kernel')->handle($request);
    self::assertEquals(200, $response->getStatusCode());
    $content = $response->getContent();
    self::assertNotFalse($content);
    $this->setRawContent($content);

    $this->assertGoogleTagEvents([
      [
        'name' => 'view_item_list',
        'data' => [
          'item_list_id' => 'catalog',
          'item_list_name' => 'Catalog',
          'items' => [
            [
              'item_name' => $variation->label(),
              'item_id' => $variation->getSku(),
              'affiliation' => $this->store->label(),
              'price' => '12.00',
            ],
          ],
        ],
      ],
    ]);
  }

}
