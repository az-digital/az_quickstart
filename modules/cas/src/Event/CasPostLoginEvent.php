<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Drupal\user\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPostLoginEvent.
 *
 * CAS dispatches this event during the authentication process after the user
 * has been logged into Drupal.
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPostLoginEvent extends Event {

  /**
   * Store the CAS property bag.
   *
   * @var \Drupal\cas\CasPropertyBag
   */
  protected $casPropertyBag;

  /**
   * The drupal user entity about to be logged in.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserInterface $account
   *   The drupal user entity about to be logged in.
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag of the current login cycle.
   */
  public function __construct(UserInterface $account, CasPropertyBag $cas_property_bag) {
    $this->account = $account;
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

  /**
   * Return the user account entity.
   *
   * @return \Drupal\user\UserInterface
   *   The user account entity.
   */
  public function getAccount() {
    return $this->account;
  }

}
