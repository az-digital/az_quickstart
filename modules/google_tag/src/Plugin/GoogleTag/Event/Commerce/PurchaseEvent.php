<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purchase event plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_purchase",
 *   event_name = "purchase",
 *   label = @Translation("Purchase (Commerce)"),
 *   dependency = "commerce_order",
 *   context_definitions = {
 *      "order" = @ContextDefinition("entity:commerce_order")
 *   }
 * )
 */
final class PurchaseEvent extends EventBase implements ContainerFactoryPluginInterface {

  use CommerceEventTrait;

  /**
   * Adjustment transformer.
   *
   * @var \Drupal\commerce_order\AdjustmentTransformerInterface
   */
  private AdjustmentTransformerInterface $transformer;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->transformer = $container->get('commerce_order.adjustment_transformer');
    return $instance;
  }

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
      'transaction_id' => $order->getOrderNumber(),
      'shipping' => array_reduce(
        $this->transformer->processAdjustments($order->collectAdjustments(['shipping'])),
        static fn (string $carry, Adjustment $adjustment) => Calculator::add($carry, $adjustment->getAmount()->getNumber()),
        '0',
      ),
      'tax' => array_reduce(
        $this->transformer->processAdjustments($order->collectAdjustments(['tax'])),
        static fn (string $carry, Adjustment $adjustment) => Calculator::add($carry, $adjustment->getAmount()->getNumber()),
        '0',
      ),
      'items' => array_map(
        function (OrderItemInterface $order_item) use ($affiliation) {
          $unit_price = $order_item->getUnitPrice();
          assert($unit_price !== NULL);
          $adjusted_price = $order_item->getAdjustedUnitPrice();
          assert($adjusted_price !== NULL);

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

          return [
            'item_id' => $item_id,
            'item_name' => $order_item->label(),
            'affiliation' => $affiliation,
            'discount' => $unit_price->subtract($adjusted_price)->getNumber(),
            'price' => $this->formatPriceNumber($unit_price),
            'quantity' => (int) $order_item->getQuantity(),
          ];
        },
        $order->getItems(),
      ),
    ];
  }

}
