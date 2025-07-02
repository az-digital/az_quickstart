<?php

declare(strict_types=1);

namespace Drupal\honeypot\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\honeypot\Event\HoneypotRejectEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Implementations of hooks defined by the Honeypot module.
 *
 * Honeypot defines four hooks, for use by other modules to alter and/or
 * enhance the behavior of Honeypot:
 * - hook_honeypot_form_protections_alter().
 * - hook_honeypot_add_form_protection().
 * - hook_honeypot_reject().
 * - hook_honeypot_time_limit().
 * These hooks are documented in honeypot.api.php.
 *
 * Only one of these, hook_honeypot_reject(), is implemented by the Honeypot
 * module.
 */
final class HoneypotHoneypotHooks {

  /**
   * Constructs a new HoneypotHoneypotHooks service.
   *
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event_dispatcher service.
   */
  public function __construct(
    protected EventDispatcherInterface $dispatcher,
  ) {}

  /**
   * Implements hook_honeypot_reject().
   *
   * Generates an event when a form submission is rejected.
   *
   * @param string $form_id
   *   Form ID for the rejected form submission.
   * @param int $uid
   *   The account identifier for the user.
   * @param string $type
   *   String indicating the reason the submission was blocked. Allowed values:
   *   - honeypot: If honeypot field was filled in.
   *   - honeypot_time: If form was completed before the configured time limit.
   *
   * @todo Only accepts two args - see above.
   */
  #[Hook('honeypot_reject')]
  public function honeypotReject(string $form_id, int $uid, string $type): void {
    if ($this->dispatcher->hasListeners(HoneypotRejectEvent::EVENT_NAME)) {
      $event = new HoneypotRejectEvent($form_id, $uid, $type);
      $this->dispatcher->dispatch($event, $event::EVENT_NAME);
    }
  }

}
