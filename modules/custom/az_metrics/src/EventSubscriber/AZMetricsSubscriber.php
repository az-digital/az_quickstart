<?php

namespace Drupal\az_metrics\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Component\Datetime\Time;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class for logging domains.
 */
class AZMetricsSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Drupal\Component\Datetime\Time definition.
   *
   * @var Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs a AZMetricsSubscriber object.
   *
   * @param \Drupal\Component\Datetime\Time $time
   *   time object.
   * @param \Drupal\Core\Database\Connection $database
   *   The factory for database connection objects.
   */
  public function __construct(Connection $connection, Time $time) {
    $this->connection = $connection;
    $this->time = $time;
  }

  /**
   * The method to store the incoming domain in the database.
   */
  public function logDomain(GetResponseEvent $event) {
    $httpHost = $event->getRequest()->getHttpHost();
    $this->connection->merge('az_metrics_domains')
      ->key('domain', $httpHost)
      ->fields([
        'domain' => $httpHost,
        'last_seen' => $this->time->getRequestTime(),
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
