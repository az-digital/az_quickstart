<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_order\Adjustment;
use Drupal\commerce_order\AdjustmentTransformerInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Calculator;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Refund event.
 *
 * @GoogleTagEvent(
 *   id = "commerce_refund",
 *   event_name = "refund",
 *   label = @Translation("Refund (Commerce)"),
 *   dependency = "commerce_order",
 *   context_definitions = {
 *      "order" = @ContextDefinition("entity:commerce_order")
 *   }
 * )
 */
final class RefundEvent extends EventBase implements ContainerFactoryPluginInterface {

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
      'items' => [],
    ];
  }

}
