<?php

namespace Drupal\cas;

/**
 * Data model for CAS property bag.
 */
class CasPropertyBag {

  /**
   * The username of the CAS user.
   *
   * @var string
   */
  protected $username;

  /**
   * The original username as it has been received from the CAS server.
   *
   * @var string
   */
  protected $originalUsername;

  /**
   * The proxy granting ticket, if supplied.
   *
   * @var string
   */
  protected $pgt;

  /**
   * An array containing attributes returned from the server.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * Contructor.
   *
   * @param string $user
   *   The username of the CAS user.
   */
  public function __construct($user) {
    $this->username = $user;
    $this->originalUsername = $user;
  }

  /**
   * Username property setter.
   *
   * @param string $user
   *   The new username.
   */
  public function setUsername($user) {
    $this->username = $user;
  }

  /**
   * Proxy granting ticket property setter.
   *
   * @param string $ticket
   *   The ticket to set as pgt.
   */
  public function setPgt($ticket) {
    $this->pgt = $ticket;
  }

  /**
   * Username property getter.
   *
   * @return string
   *   The username property.
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * Returns the original username property.
   *
   * @return string
   *   The original username.
   */
  public function getOriginalUsername() {
    return $this->originalUsername;
  }

  /**
   * Proxy granting ticket getter.
   *
   * @return string
   *   The pgt property.
   */
  public function getPgt() {
    return $this->pgt;
  }

  /**
   * Attributes property setter.
   *
   * @param array $cas_attributes
   *   An associative array containing attribute names as keys.
   */
  public function setAttributes(array $cas_attributes) {
    $this->attributes = $cas_attributes;
  }

  /**
   * Cas attributes getter.
   *
   * @return array
   *   The attributes property.
   */
  public function getAttributes() {
    return $this->attributes;
  }

  /**
   * Adds a single attribute.
   *
   * @param string $name
   *   The attribute name.
   * @param mixed $value
   *   The attribute value.
   */
  public function setAttribute($name, $value) {
    $this->attributes[$name] = $value;
  }

  /**
   * Returns a single attribute if exists.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return mixed|null
   *   The attribute value, or NULL if it does not exist.
   */
  public function getAttribute($name) {
    return $this->hasAttribute($name) ? $this->attributes[$name] : NULL;
  }

  /**
   * Checks whether an attribute exists.
   *
   * @param string $name
   *   The name of the attribute.
   *
   * @return bool
   *   TRUE if the attribute exists, FALSE otherwise.
   */
  public function hasAttribute($name) {
    return isset($this->attributes[$name]);
  }

}
