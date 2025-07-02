<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View Item list plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_view_item_list",
 *   event_name = "view_item_list",
 *   label = @Translation("View item list"),
 *   description = @Translation("Log this event when the user has been presented with a list of items of a certain category."),
 *   dependency = "commerce_product",
 *   context_definitions = {
 *      "item_list_id" = @ContextDefinition("string", required = FALSE),
 *      "item_list_name" = @ContextDefinition("string", required = FALSE),
 *      "items" = @ContextDefinition("entity:commerce_product_variation", multiple = TRUE, required = FALSE),
 *   }
 * )
 *
 * @todo allow support of generic purchasable entity types.
 */
final class ViewItemList extends EventBase implements ContainerFactoryPluginInterface {

  use CommerceEventTrait;

  /**
   * Current Store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  private CurrentStoreInterface $currentStore;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self($configuration, $plugin_id, $plugin_definition);
    $instance->currentStore = $container->get('commerce_store.current_store');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getData(): array {
    $items = $this->getContextValue('items');
    $affiliation = $this->currentStore->getStore()->label();

    return [
      'item_list_id' => $this->getContextValue('item_list_id'),
      'item_list_name' => $this->getContextValue('item_list_name'),
      'items' => array_map(function (ProductVariationInterface $item) use ($affiliation) {
        return [
          'item_name' => $item->label(),
          'item_id' => $item->getSku(),
          'affiliation' => $affiliation,
          'price' => $this->formatPriceNumber($item->getPrice()),
        ];
      }, $items),
    ];
  }

}
