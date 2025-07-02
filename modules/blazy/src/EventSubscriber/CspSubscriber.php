<?php

namespace Drupal\blazy\EventSubscriber;

use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter CSP policy for Blazy.
 */
class CspSubscriber implements EventSubscriberInterface {

  /**
   * The Library Dependency Resolver service.
   *
   * @var \Drupal\Core\Asset\LibraryDependencyResolverInterface
   */
  private $libraryDependencyResolver;

  /**
   * CspSubscriber constructor.
   *
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $libraryDependencyResolver
   *   The Library Dependency Resolver Service.
   */
  public function __construct(LibraryDependencyResolverInterface $libraryDependencyResolver) {
    $this->libraryDependencyResolver = $libraryDependencyResolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];

    if (class_exists(CspEvents::class)) {
      $events[CspEvents::POLICY_ALTER] = ['onCspPolicyAlter'];
    }
    return $events;
  }

  /**
   * Alter CSP policy to allow data img-src.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $alterEvent
   *   The Policy Alter event.
   *
   * @phpstan-ignore-next-line
   */
  public function onCspPolicyAlter(PolicyAlterEvent $alterEvent) : void {
    /* @phpstan-ignore-next-line */
    $policy = $alterEvent->getPolicy();
    /* @phpstan-ignore-next-line */
    $response = $alterEvent->getResponse();

    if ($response instanceof AttachmentsInterface) {
      $attachments = $response->getAttachments();
      $libraries = isset($attachments['library']) ?
        $this->libraryDependencyResolver->getLibrariesWithDependencies($attachments['library']) :
        [];

      if (in_array('blazy/load', $libraries)) {
        $policy->fallbackAwareAppendIfEnabled('img-src', ['data:']);
      }
    }
  }

}
