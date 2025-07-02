<?php

namespace Drupal\access_unpublished;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Service to handle the current token.
 */
class TokenGetter implements EventSubscriberInterface {

  /**
   * Access unpublished config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The current token.
   *
   * @var string
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get('access_unpublished.settings');
  }

  /**
   * Get the current token.
   *
   * @return string
   *   The token.
   */
  public function getToken() {
    return $this->token;
  }

  /**
   * Set the current token.
   *
   * @param string $token
   *   The token.
   */
  public function setToken($token) {
    $this->token = $token;
  }

  /**
   * Set the token from the current request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function setTokenFromRequest(RequestEvent $event) {
    $tokenKey = $this->config->get('hash_key');
    if ($event->getRequest()->query->has($tokenKey)) {
      $this->setToken($event->getRequest()->query->get($tokenKey));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['setTokenFromRequest', 50];
    return $events;
  }

}
