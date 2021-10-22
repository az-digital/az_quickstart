<?php

namespace Drupal\az_security\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for attaching HTTP response headers.
 */
class AZSecuritySubscriber implements EventSubscriberInterface {

  /**
   * The config for the az_security module.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructs an AZSecuritySubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('az_security.settings');
  }

  /**
   * The method to add http headers to a response.
   */
  public function onRespond(ResponseEvent $event) {
    $response = $event->getResponse();
    $headers = $this->config->get('headers') ?? [];

    if ($headers['report_to']['enabled'] ?? FALSE) {
      $response->headers->set(
        'Report-To',
        trim(json_encode($headers['report_to']['objects'], JSON_UNESCAPED_SLASHES), '[]')
      );
    }

    if ($headers['other']['enabled'] ?? FALSE) {
      foreach ($headers['other']['objects'] ?? [] as $header) {
        $response->headers->set(
          $header['name'],
          $header['value']
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onRespond'];
    return $events;
  }

}
