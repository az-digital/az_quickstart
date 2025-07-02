<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event fired after CAS validation happens.
 *
 * CAS dispatches this event just after the ticket is validated by the CAS
 * server and the CasPropertyBag object is created with relevant data from the
 * response.
 *
 * Subscribers of this event can parse the response from the CAS server and
 * modify the CasPropertyBag.
 */
class CasPostValidateEvent extends Event {

  /**
   * The raw validation response data from CAS server.
   *
   * @var string
   */
  protected $responseData;

  /**
   * The bag of properties extracted from the response after the validation.
   *
   * @var \Drupal\cas\CasPropertyBag
   */
  protected $casPropertyBag;

  /**
   * CasPostValidateEvent constructor.
   *
   * @param string $response_data
   *   The raw validation response data from CAS server.
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The bag of properties extracted from the response after the validation.
   */
  public function __construct($response_data, CasPropertyBag $cas_property_bag) {
    $this->responseData = $response_data;
    $this->casPropertyBag = $cas_property_bag;
  }

  /**
   * Returns the CasPropertyBag object.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The property bag
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

  /**
   * Returns the responseData string.
   *
   * @return string
   *   The raw validation response data from CAS server.
   */
  public function getResponseData() {
    return $this->responseData;
  }

}
