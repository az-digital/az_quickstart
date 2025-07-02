<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreUserLoadEvent.
 *
 * The CAS module dispatches this event during the authentication process just
 * before an attempt is made to find a local Drupal user account that's
 * associated with the user attempting to login.
 *
 * Subscribers to this event can:
 *  - Alter the CAS username that is used when looking up an existing Drupal
 *    user account.
 */
class CasPreUserLoadEvent extends Event {

  /**
   * Store the CAS property bag.
   *
   * @var \Drupal\cas\CasPropertyBag
   *   The CasPropertyBag for context.
   */
  protected $casPropertyBag;

  /**
   * Constructor.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag of the current login cycle.
   */
  public function __construct(CasPropertyBag $cas_property_bag) {
    $this->casPropertyBag = $cas_property_bag;
  }

  /**
   * CasPropertyBag getter.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The casPropertyBag property.
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

}
