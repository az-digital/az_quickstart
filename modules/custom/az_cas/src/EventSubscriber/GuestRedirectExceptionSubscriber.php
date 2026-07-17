<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\az_cas\Exception\GuestRedirectException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for handling guest redirect exceptions.
 */
class GuestRedirectExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs a new GuestRedirectExceptionSubscriber.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('az_cas');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Use a high priority to ensure we catch the exception before other
    // handlers.
    $events[KernelEvents::EXCEPTION][] = ['onException', 100];
    return $events;
  }

  /**
   * Handles exceptions.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The exception event.
   */
  public function onException(ExceptionEvent $event) {
    $exception = $event->getThrowable();

    // Check if this is our special guest redirect exception.
    if ($exception instanceof GuestRedirectException) {
      $redirect_url = $exception->getRedirectUrl();

      // Create a redirect response.
      $response = new TrustedRedirectResponse($redirect_url);

      // Set the response on the event.
      $event->setResponse($response);
    }
  }

}
