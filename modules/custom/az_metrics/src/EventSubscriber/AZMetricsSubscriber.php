<?php

namespace Drupal\az_metrics\EventSubscriber;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Database\Connection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class for logging domains.
 */
class AZMetricsSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Drupal\Component\Datetime\Time definition.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs a AZMetricsSubscriber object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Database connection object.
   * @param \Drupal\Component\Datetime\Time $time
   *   Time object.
   */
  public function __construct(Connection $connection, Time $time) {
    $this->connection = $connection;
    $this->time = $time;
  }

  /**
   * The method to store the incoming domain in the database.
   */
  public function logDomain(RequestEvent $event) {
    $httpHost = $event->getRequest()->getHttpHost();
    $requestTime = $this->time->getRequestTime();
    $this->connection->merge('az_metrics_domains')
      ->key('domain', $httpHost)
      ->fields([
        'domain' => $httpHost,
        'last_seen' => $requestTime,
      ])->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['logDomain'];
    return $events;
  }

}
