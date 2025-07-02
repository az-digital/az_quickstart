<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;

/**
 * Add to cart event.
 *
 * @GoogleTagEvent(
 *   id = "commerce_add_to_cart",
 *   event_name = "add_to_cart",
 *   label = @Translation("Add to cart"),
 *   description = @Translation("This event signifies that an item was added to a cart for purchase."),
 *   dependency = "commerce_cart",
 *   context_definitions = {
 *      "item" = @ContextDefinition("entity:commerce_order_item")
 *   }
 * )
 */
final class AddToCartEvent extends EventBase {

  use CommerceEventTrait;

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    // @todo leverage config for token replacement.
    $order_item = $this->getContextValue('item');
    assert($order_item instanceof OrderItemInterface);
    $unit_price = $order_item->getUnitPrice();
    assert($unit_price !== NULL);
    $adjusted_price = $order_item->getAdjustedUnitPrice();
    assert($adjusted_price !== NULL);

    $item_data = [
      'item_name' => $order_item->label(),
      'affiliation' => $order_item->getOrder()->getStore()->label(),
      'discount' => $unit_price->subtract($adjusted_price)->getNumber(),
      'price' => $this->formatPriceNumber($unit_price),
      'quantity' => (int) $order_item->getQuantity(),
    ];
    $purchased_entity = $order_item->getPurchasedEntity();
    if ($purchased_entity instanceof ProductVariationInterface) {
      $item_data['item_id'] = $purchased_entity->getSku();
    }
    elseif ($purchased_entity !== NULL) {
      $item_data['item_id'] = $purchased_entity->id();
    }
    return [
      'currency' => $unit_price->getCurrencyCode(),
      'value' => $this->formatPriceNumber($adjusted_price),
      'items' => [
        $item_data,
      ],
    ];
  }

}
