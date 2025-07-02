<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\google_tag\Ajax\GoogleTagEventCommand;
use Drupal\google_tag\EventCollectorInterface;
use Drupal\google_tag\TagContainerResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Acts on Drupal Kernel Response event.
 */
final class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * Tag Container Resolver Service.
   *
   * @var \Drupal\google_tag\TagContainerResolver
   */
  private TagContainerResolver $tagResolver;

  /**
   * Event Collector Service.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $collector;

  /**
   * ResponseSubscriber constructor.
   *
   * @param \Drupal\google_tag\TagContainerResolver $tagResolver
   *   Google tag resolver.
   * @param \Drupal\google_tag\EventCollectorInterface $collector
   *   Collector.
   */
  public function __construct(TagContainerResolver $tagResolver, EventCollectorInterface $collector) {
    $this->tagResolver = $tagResolver;
    $this->collector = $collector;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::RESPONSE => ['onResponse', 0],
    ];
  }

  /**
   * Fires Ajax command to add gtag events for Ajax responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   Response event object.
   */
  public function onResponse(ResponseEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $response = $event->getResponse();
    if (!$response instanceof AjaxResponse) {
      return;
    }
    $config = $this->tagResolver->resolve();
    if ($config) {
      foreach ($this->collector->getEvents() as $tag_event) {
        $response->addCommand(new GoogleTagEventCommand(
          $tag_event->getName(),
          $tag_event->getData()
        ));
      }
    }
  }

}
