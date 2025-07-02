<?php

namespace Drupal\devel\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listener for handling PHP errors.
 */
class ErrorHandlerSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   */
  protected AccountProxyInterface $account;

  /**
   * ErrorHandlerSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user.
   */
  public function __construct(AccountProxyInterface $account) {
    $this->account = $account;
  }

  /**
   * Register devel error handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent|null $event
   *   The event to process.
   */
  public function registerErrorHandler(RequestEvent $event = NULL): void {
    if (!$this->account->hasPermission('access devel information')) {
      return;
    }

    devel_set_handler(devel_get_handlers());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Runs as soon as possible in the request but after
    // AuthenticationSubscriber (priority 300) because you need to access to
    // the current user for determine whether register the devel error handler
    // or not.
    $events[KernelEvents::REQUEST][] = ['registerErrorHandler', 256];

    return $events;
  }

}
