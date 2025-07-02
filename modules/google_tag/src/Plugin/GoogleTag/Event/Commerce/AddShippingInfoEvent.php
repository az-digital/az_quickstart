<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;

/**
 * Shipping info event plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_add_shipping_info",
 *   event_name = "add_shipping_info",
 *   label = @Translation("Add_shipping info"),
 *   dependency = "commerce_shipping",
 *   context_definitions = {
 *      "order" = @ContextDefinition("entity:commerce_order")
 *   }
 * )
 */
final class AddShippingInfoEvent extends EventBase {

}
