<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\CurrentStoreInterface;
use Drupal\commerce_wishlist\Entity\WishlistItemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add to wish list event plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_add_to_wishlist",
 *   event_name = "add_to_wishlist",
 *   label = @Translation("Add to wishlist"),
 *   description = @Translation("The event signifies that an item was added to a wishlist. Use this event to identify popular gift items in your app."),
 *   dependency = "commerce_wishlist",
 *   context_definitions = {
 *      "item" = @ContextDefinition("entity:commerce_wishlist_item")
 *   }
 * )
 */
final class AddToWishlistEvent extends EventBase implements ContainerFactoryPluginInterface {

  use CommerceEventTrait;

  /**
   * Current store.
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
    // @todo leverage config for token replacement.
    $wishlist_item = $this->getContextValue('item');
    assert($wishlist_item instanceof WishlistItemInterface);

    $item_data = [
      'item_name' => $wishlist_item->label(),
      'quantity' => (int) $wishlist_item->getQuantity(),
      'affiliation' => $this->currentStore->getStore()->label(),
    ];
    $purchased_entity = $wishlist_item->getPurchasableEntity();
    if ($purchased_entity instanceof ProductVariationInterface) {
      $item_data['item_id'] = $purchased_entity->getSku();
    }
    elseif ($purchased_entity !== NULL) {
      $item_data['item_id'] = $purchased_entity->id();
    }
    return [
      'currency' => $purchased_entity->getPrice()->getCurrencyCode(),
      'value' => $this->formatPriceNumber($purchased_entity->getPrice()),
      'items' => [
        $item_data,
      ],
    ];
  }

}
