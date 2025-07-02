<?php

namespace Drupal\cas\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreValidateEvent.
 *
 * CAS dispatches this event during the authentication process when the CAS
 * service ticket is being validated.
 *
 * Subscribers of this event can:
 *  - Set a non-standard validation path on the CAS server. Usually
 *    this value is determined automatically based on the CAS protocol version
 *    of the CAS server.
 *  - Add query string parameters to the CAS server validation URL. This is
 *    useful if your CAS server requires some custom data during the ticket
 *    validation process.
 */
class CasPreValidateEvent extends Event {

  /**
   * Validation path.
   *
   * @var string
   */
  protected $validationPath;

  /**
   * Query parameters to add to the validation URL.
   *
   * @var array
   */
  protected $parameters = [];

  /**
   * Constructor.
   *
   * @param string $validation_path
   *   The validation path.
   * @param array $parameters
   *   Query parameters to add to the validation URL.
   */
  public function __construct($validation_path, array $parameters) {
    $this->validationPath = $validation_path;
    $this->parameters = $parameters;
  }

  /**
   * Getter for $validationPath.
   *
   * @return string
   *   The validation path.
   */
  public function getValidationPath() {
    return $this->validationPath;
  }

  /**
   * Setter for $validationPath.
   *
   * @param string $validation_path
   *   The validation path to be used.
   */
  public function setValidationPath($validation_path) {
    $this->validationPath = $validation_path;
  }

  /**
   * Getter for $parameters.
   *
   * @return array
   *   Query parameters to add to the validation URL.
   */
  public function getParameters() {
    return $this->parameters;
  }

  /**
   * Sets a single parameter.
   *
   * @param string $key
   *   The key of the parameter.
   * @param mixed $value
   *   The value of the parameter.
   */
  public function setParameter($key, $value) {
    $this->parameters[$key] = $value;
  }

  /**
   * Adds an array of parameters the existing set.
   *
   * @param array $parameters
   *   The parameters to be merged.
   */
  public function addParameters(array $parameters) {
    $this->parameters = array_merge($this->parameters, $parameters);
  }

}
