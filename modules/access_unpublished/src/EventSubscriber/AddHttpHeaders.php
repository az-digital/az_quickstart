<?php

namespace Drupal\access_unpublished\EventSubscriber;

use Drupal\access_unpublished\TokenGetter;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add new HTTP headers as added in settings form.
 */
class AddHttpHeaders implements EventSubscriberInterface {

  /**
   * Access Unpublished custom configurations.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The token getter service.
   *
   * @var \Drupal\access_unpublished\TokenGetter
   */
  protected $tokenGetter;

  /**
   * Constructs a new response subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   * @param \Drupal\access_unpublished\TokenGetter $tokenGetter
   *   The token getter service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TokenGetter $tokenGetter) {
    $this->config = $configFactory->get('access_unpublished.settings');
    $this->tokenGetter = $tokenGetter;
  }

  /**
   * Set HTTP headers configured on admin/config/content/access_unpublished.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Event.
   */
  public function onResponse(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    if ($this->tokenGetter->getToken()) {
      foreach ($this->config->get('modify_http_headers') as $key => $header) {
        // Must remove the existing header if settings a new value.
        if ($event->getResponse()->headers->has($key)) {
          $event->getResponse()->headers->remove($key);
        }
        $event->getResponse()->headers->set($key, $header);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

}
