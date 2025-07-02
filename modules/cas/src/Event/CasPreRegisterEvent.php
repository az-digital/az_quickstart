<?php

namespace Drupal\cas\Event;

use Drupal\cas\CasPropertyBag;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class CasPreRegisterEvent.
 *
 * The CAS module dispatches this event during the authentication process just
 * before a user is automatically registered to Drupal if:
 *  - Automatic user registration is enabled in the CAS module settings.
 *  - No existing Drupal account can be found that's associated with the
 *    CAS username of the user attempting authentication.
 *
 * Subscribers to this event can:
 *  - Prevent a Drupal account from being created for this user (thereby also
 *    preventing the user from logging in).
 *  - Change the username that will be assigned to the Drupal account. By
 *    default it is the same as the CAS username.
 *  - Set properties on the user account that will be created, like user roles
 *    or a custom first name field (for example by populating it with data from
 *    the CAS attributes available in $casPropertyBag).
 *
 * Any CAS attributes will be available via the $casPropertyBag data object.
 */
class CasPreRegisterEvent extends Event {

  /**
   * The user information returned from the CAS server.
   *
   * @var \Drupal\cas\CasPropertyBag
   */
  protected $casPropertyBag;

  /**
   * Determines if this user will be allowed to auto-register or not.
   *
   * @var bool
   */
  protected $allowAutomaticRegistration = TRUE;

  /**
   * The username that will be assigned to the Drupal user account.
   *
   * By default this will be populated with the CAS username.
   *
   * @var string
   */
  protected $drupalUsername;

  /**
   * An array of property values to assign to the user account on registration.
   *
   * @var array
   */
  protected $propertyValues = [];

  /**
   * Contains the reason of registration cancellation.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string|null
   */
  protected $cancelRegistrationReason;

  /**
   * Contructor.
   *
   * @param \Drupal\cas\CasPropertyBag $cas_property_bag
   *   The CasPropertyBag for context.
   */
  public function __construct(CasPropertyBag $cas_property_bag) {
    $this->casPropertyBag = $cas_property_bag;
    $this->drupalUsername = $cas_property_bag->getUsername();
  }

  /**
   * Return the CasPropertyBag of the event.
   *
   * @return \Drupal\cas\CasPropertyBag
   *   The $casPropertyBag property.
   */
  public function getCasPropertyBag() {
    return $this->casPropertyBag;
  }

  /**
   * Retrieve the username that will be assigned to the Drupal account.
   *
   * @return string
   *   The username.
   */
  public function getDrupalUsername() {
    return $this->drupalUsername;
  }

  /**
   * Assign a different username to the Drupal account that is to be registered.
   *
   * @param string $username
   *   The username.
   */
  public function setDrupalUsername($username) {
    $this->drupalUsername = $username;
  }

  /**
   * Sets the allow auto registration proprety.
   *
   * @param bool $allow_automatic_registration
   *   TRUE to allow auto registration, FALSE to deny it.
   *
   * @deprecated in cas:2.0.0 and is removed from cas:3.0.0. Instead, use
   *   \Drupal\cas\Event\CasPreRegisterEvent::allowAutomaticRegistration() or
   *   \Drupal\cas\Event\CasPreRegisterEvent::cancelAutomaticRegistration().
   *
   * @see https://www.drupal.org/project/cas/issues/3221111
   */
  public function setAllowAutomaticRegistration($allow_automatic_registration) {
    @trigger_error('CasPreRegisterEvent::setAllowAutomaticRegistration() is deprecated in cas:2.0.0 and is removed from cas:3.0.0. Instead, use \Drupal\cas\Event\CasPreRegisterEvent::allowAutomaticRegistration() or \Drupal\cas\Event\CasPreRegisterEvent::cancelAutomaticRegistration(). See https://www.drupal.org/project/cas/issues/3221111', E_USER_DEPRECATED);
    if ($allow_automatic_registration) {
      $this->allowAutomaticRegistration();
    }
    else {
      $this->cancelAutomaticRegistration();
    }
  }

  /**
   * Return if this user is allowed to be auto-registered or not.
   *
   * @return bool
   *   TRUE if the user is allowed to be registered, FALSE otherwise.
   */
  public function getAllowAutomaticRegistration() {
    return $this->allowAutomaticRegistration;
  }

  /**
   * Getter for propertyValues.
   *
   * @return array
   *   The user property values.
   */
  public function getPropertyValues() {
    return $this->propertyValues;
  }

  /**
   * Set a single property value for the user entity on registration.
   *
   * @param string $property
   *   The user entity property to set.
   * @param mixed $value
   *   The value of the property.
   */
  public function setPropertyValue($property, $value) {
    $this->propertyValues[$property] = $value;
  }

  /**
   * Set an array of property values for the user entity on registration.
   *
   * @param array $property_values
   *   The property values to set with each key corresponding to the property.
   */
  public function setPropertyValues(array $property_values) {
    $this->propertyValues = array_merge($this->propertyValues, $property_values);
  }

  /**
   * Returns the reason of the registration cancellation.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason of registration cancellation.
   */
  public function getCancelRegistrationReason() {
    return $this->cancelRegistrationReason;
  }

  /**
   * Allows automatic registration.
   *
   * @return $this
   */
  public function allowAutomaticRegistration(): self {
    // Activate the allow automatic registration.
    $this->allowAutomaticRegistration = TRUE;
    $this->cancelRegistrationReason = NULL;
    return $this;
  }

  /**
   * Cancels automatic registration.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string|null $reason
   *   The reason of automatic cancellation property to set.
   */
  public function cancelAutomaticRegistration($reason = NULL): self {
    // Set the reason code into the property.
    $this->cancelRegistrationReason = $reason;
    // Deactivate the allow automatic registration.
    $this->allowAutomaticRegistration = FALSE;
    return $this;
  }

}
