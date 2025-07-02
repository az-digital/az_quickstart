<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event;

/**
 * Webform purchase event.
 *
 * @GoogleTagEvent(
 *   id = "webform_purchase",
 *   label = @Translation("Purchase (Webform)"),
 *   dependency = "webform",
 *   context_definitions = {
 *      "submission" = @ContextDefinition("entity:webform_submission")
 *   }
 * )
 */
final class WebformPurchase extends EventBase {

}
