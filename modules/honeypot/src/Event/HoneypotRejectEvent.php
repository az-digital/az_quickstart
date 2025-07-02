<?php

namespace Drupal\honeypot\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event that is fired when Honeypot rejects a form submission.
 *
 * @see hook_honeypot_reject()
 */
class HoneypotRejectEvent extends Event {

  const EVENT_NAME = 'honeypot.form_submission_rejected';

  /**
   * Form ID of the form the user was disallowed from submitting.
   *
   * @var string
   *
   * @phpcs:disable Drupal.NamingConventions.ValidVariableName.LowerCamelName
   */
  protected $form_id;
  // phpcs:enable

  /**
   * The user account ID.
   *
   * @var int
   */
  protected $uid;

  /**
   * String indicating the reason the submission was blocked.
   *
   * Allowed values:
   * - honeypot: If honeypot field was filled in.
   * - honeypot_time: If form was completed before the configured time limit.
   *
   * @var string
   */
  protected $type;

  /**
   * Constructs the object.
   *
   * @param string $form_id
   *   Form ID of the form the user was disallowed from submitting.
   * @param int $uid
   *   The account of the user after unblocking.
   * @param string $type
   *   String indicating the reason the submission was blocked. Allowed values:
   *   - honeypot: If honeypot field was filled in.
   *   - honeypot_time: If form was completed before the configured time limit.
   */
  public function __construct(string $form_id, int $uid, string $type) {
    $this->form_id = $form_id;
    $this->uid = $uid;
    $this->type = $type;
  }

}
