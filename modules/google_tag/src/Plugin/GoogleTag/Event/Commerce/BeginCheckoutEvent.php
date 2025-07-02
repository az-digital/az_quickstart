<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;

/**
 * Checkout begin event plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_begin_checkout",
 *   event_name = "begin_checkout",
 *   label = @Translation("Begin checkout"),
 *   dependency = "commerce_checkout",
 *   context_definitions = {
 *      "order" = @ContextDefinition("entity:commerce_order")
 *   }
 * )
 */
final class BeginCheckoutEvent extends EventBase {

  use CommerceEventTrait;

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    $order = $this->getContextValue('order');
    assert($order instanceof OrderInterface);
    $order_total = $order->getTotalPrice();

    $affiliation = $order->getStore()->label();

    return [
      'currency' => $order_total->getCurrencyCode(),
      'value' => $this->formatPriceNumber($order_total),
      'items' => array_map(
        function (OrderItemInterface $order_item) use ($affiliation) {

          $purchased_entity = $order_item->getPurchasedEntity();
          if ($purchased_entity instanceof ProductVariationInterface) {
            $item_id = $purchased_entity->getSku();
          }
          elseif ($purchased_entity !== NULL) {
            $item_id = $purchased_entity->id();
          }
          else {
            $item_id = '';
          }
          $unit_price = $order_item->getUnitPrice();
          assert($unit_price !== NULL);
          $adjusted_price = $order_item->getAdjustedUnitPrice();
          assert($adjusted_price !== NULL);

          return [
            'item_id' => $item_id,
            'item_name' => $order_item->label(),
            'affiliation' => $affiliation,
            'discount' => $this->formatPriceNumber($unit_price->subtract($adjusted_price)),
            'price' => $this->formatPriceNumber($unit_price),
            'quantity' => (int) $order_item->getQuantity(),
          ];
        },
        $order->getItems(),
      ),
    ];
  }

}
