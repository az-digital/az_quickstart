<?php

declare(strict_types=1);

namespace Drupal\google_tag\Plugin\GoogleTag\Event\Commerce;

use Drupal\commerce_price\Price;

/**
 * Commerce event trait to format price number.
 */
trait CommerceEventTrait {

  /**
   * Formats price number.
   *
   * @param \Drupal\commerce_price\Price $price
   *   Price object.
   *
   * @return string
   *   Formatted price.
   */
  protected function formatPriceNumber(Price $price): string {
    return number_format((float) $price->getNumber(), 2, '.', '');
  }

}
