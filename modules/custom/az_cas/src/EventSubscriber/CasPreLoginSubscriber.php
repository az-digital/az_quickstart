<?php

namespace Drupal\az_cas\EventSubscriber;

use Drupal\cas\Event\CasPreLoginEvent;
use Drupal\cas\Service\CasHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Event subscriber for CAS pre-login events.
 */
class CasPreLoginSubscriber implements EventSubscriberInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new CasPreLoginSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
  ) {
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[CasHelper::EVENT_PRE_LOGIN][] = ['onCasPreLogin', 100];
    return $events;
  }

  /**
   * Respond to CAS pre-login event.
   *
   * @param \Drupal\cas\Event\CasPreLoginEvent $event
   *   The CAS pre-login event.
   */
  public function onCasPreLogin(CasPreLoginEvent $event) {
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $guest_data = $session->get('az_cas_guest');

    // If this is a guest session, cancel the login process.
    if (!empty($guest_data['authenticated'])) {
      // Cancel the login process without showing an error message.
      $event->cancelLogin();
    }
  }

}
