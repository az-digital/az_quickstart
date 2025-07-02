<?php

namespace Drupal\cas\Exception;

/**
 * Exception occurring on login failure.
 */
class CasLoginException extends \Exception {

  /**
   * Auto registration turned off, and local account does not exist.
   */
  const NO_LOCAL_ACCOUNT = 1;

  /**
   * Auto reg turned on, but subscriber denied auto reg.
   */
  const SUBSCRIBER_DENIED_REG = 2;

  /**
   * Could not log user in, because Drupal account is blocked.
   */
  const ACCOUNT_BLOCKED = 3;

  /**
   * Event listener prevented login.
   */
  const SUBSCRIBER_DENIED_LOGIN = 4;

  /**
   * Error parsing CAS attributes during login.
   */
  const ATTRIBUTE_PARSING_ERROR = 5;

  /**
   * Auto registration attempted to register Drupal user that already exists.
   */
  const USERNAME_ALREADY_EXISTS = 6;

  /**
   * Cannot log in until admins are not unblocking the new account.
   */
  const ADMIN_APPROVAL_REQUIRED = 7;

  /**
   * A user message when login failed on a subscriber cancellation.
   *
   * @var \Drupal\Component\Render\MarkupInterface|string
   */
  protected $subscriberCancelReason;

  /**
   * Sets a user message when login failed on a subscriber cancellation.
   *
   * @param \Drupal\Component\Render\MarkupInterface|string $reason
   *   A user message to be set along with the exception.
   *
   * @return $this
   */
  public function setSubscriberCancelReason($reason) {
    $code = $this->getCode();
    if ($code === self::SUBSCRIBER_DENIED_LOGIN || $code === self::SUBSCRIBER_DENIED_REG) {
      $this->subscriberCancelReason = $reason;
    }
    return $this;
  }

  /**
   * Returns the user message if login failed on a subscriber cancellation.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|null
   *   The reason why login failed, if any.
   */
  public function getSubscriberCancelReason() {
    return $this->subscriberCancelReason;
  }

}
