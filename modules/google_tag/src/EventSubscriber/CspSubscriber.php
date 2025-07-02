<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Csp Policy alter event subscriber.
 */
final class CspSubscriber implements EventSubscriberInterface {

  /**
   * GA domains.
   */
  private const DOMAINS = [
    'https://www.google-analytics.com',
    'https://www.googletagmanager.com',
  ];

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      CspEvents::POLICY_ALTER => 'alterPolicy',
    ];
  }

  /**
   * Fires an event on csp policy alter event.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $event
   *   Event object.
   */
  public function alterPolicy(PolicyAlterEvent $event): void {
    $policy = $event->getPolicy();
    if ($policy->hasDirective('img-src')) {
      $policy->appendDirective('img-src', self::DOMAINS);
    }
    elseif ($policy->hasDirective('default-src')) {
      $imgDirective = array_merge($policy->getDirective('default-src'), self::DOMAINS);
      $policy->setDirective('img-src', $imgDirective);
    }
    if ($policy->hasDirective('connect-src')) {
      $policy->appendDirective('connect-src', self::DOMAINS);
    }
    elseif ($policy->hasDirective('default-src')) {
      $connectDirective = array_merge($policy->getDirective('default-src'), self::DOMAINS);
      $policy->setDirective('connect-src', $connectDirective);
    }
  }

}
