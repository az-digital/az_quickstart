<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\google_tag\Plugin\GoogleTag\Event\EventBase;

/**
 * Add payment info event plugin.
 *
 * @GoogleTagEvent(
 *   id = "commerce_add_payment_info",
 *   event_name = "add_payment_info",
 *   label = @Translation("Add payment info"),
 *   dependency = "commerce_checkout",
 *   context_definitions = {
 *      "order" = @ContextDefinition("entity:commerce_order")
 *   }
 * )
 */
final class AddPaymentInfoEvent extends EventBase {

}
