<?php

declare(strict_types=1);

use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\Entity\Store;
use Drupal\Component\Utility\Random;
use Drupal\google_tag\Entity\TagContainer;
use Drupal\TestSite\TestSetupInterface;

/**
 * Commerce site install setup.
 */
final class TestCommerceSiteInstallTestScript implements TestSetupInterface {

  /**
   * {@inheritDoc}
   */
  public function setup() {
    // @phpstan-ignore-next-line
    \Drupal::service('module_installer')->install([
      'test_page_test',
      'google_tag',
      'google_tag_test',
      'commerce_product',
      'commerce_cart',
      'commerce_checkout',
    ]);
    // @todo don't always create one, write command to do so.
    TagContainer::create([
      'id' => 'foo',
      'tag_container_ids' => [
        'GT-XXXXXX',
        'G-XXXXXX',
        'AW-XXXXXX',
        'DC-XXXXXX',
        'UA-XXXXXX',
      ],
      'events' => [
        'login' => [],
        'sign_up' => [],
        'route_name' => [],
        'commerce_add_to_cart' => [],
        'commerce_begin_checkout' => [],
        'commerce_purchase' => [],
        'commerce_remove_from_cart' => [],
        'commerce_view_item' => [],
      ],
    ])->save();

    // @phpstan-ignore-next-line
    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('USD');
    $store = Store::create([
      'type' => 'online',
      'uid' => 1,
      'name' => 'FooBar Store',
      'mail' => 'foo@example.com',
      'default_currency' => 'USD',
      'timezone' => 'Australia/Sydney',
      'address' => [
        'country_code' => 'US',
        'address_line1' => (new Random())->string(8, TRUE),
        'locality' => (new Random())->string(5, TRUE),
        'administrative_area' => 'WI',
        'postal_code' => '53597',
      ],
      'billing_countries' => ['US'],
      'is_default' => TRUE,
    ]);
    $store->save();

    $attribute = ProductAttribute::create([
      'id' => 'color',
      'label' => 'Color',
    ]);
    $attribute->save();
    $color_blue = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Blue',
    ]);
    $color_blue->save();
    $color_red = ProductAttributeValue::create([
      'attribute' => 'color',
      'name' => 'Red',
    ]);
    $color_red->save();

    // @phpstan-ignore-next-line
    \Drupal::service('commerce_product.attribute_field_manager')->createField(
      $attribute,
      'default',
    );

    $variation1 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'ABC123',
      'price' => [
        'number' => '12.00',
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_blue,
    ]);
    $variation1->save();
    $variation2 = ProductVariation::create([
      'type' => 'default',
      'sku' => 'DEF456',
      'price' => [
        'number' => '12.00',
        'currency_code' => 'USD',
      ],
      'attribute_color' => $color_red,
    ]);
    $variation2->save();
    $product = Product::create([
      'type' => 'default',
      'title' => 'FooBaz Widget',
      'stores' => [$store],
      'variations' => [$variation1, $variation2],
    ]);
    $product->save();
  }

}
