<?php

declare(strict_types=1);

namespace Drupal\google_tag\EventSubscriber;

use Drupal\Core\Render\Element;
use Drupal\google_tag\EventCollectorInterface;
use Drupal\search_api\Event\ProcessingResultsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Search API subscriber.
 */
final class SearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Event Collector Service.
   *
   * @var \Drupal\google_tag\EventCollectorInterface
   */
  private EventCollectorInterface $collector;

  /**
   * SearchApiSubscriber constructor.
   *
   * @param \Drupal\google_tag\EventCollectorInterface $collector
   *   Collector service.
   */
  public function __construct(EventCollectorInterface $collector) {
    $this->collector = $collector;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      SearchApiEvents::PROCESSING_RESULTS => 'onSearch',
    ];
  }

  /**
   * Fires an event on search.
   *
   * @param \Drupal\search_api\Event\ProcessingResultsEvent $event
   *   Event object.
   */
  public function onSearch(ProcessingResultsEvent $event) {
    $keys = $event->getResults()->getQuery()->getKeys();

    if ($keys !== NULL) {
      if (is_array($keys)) {
        // Ensure all elements of $keys are arrays.
        $keys = array_map(function ($key) {
          return is_array($key) ? $key : [$key];
        }, $keys);
        // Flatten the array.
        $keys = array_reduce($keys, 'array_merge', []);
      }
      else {
        // If $keys is not an array, convert it to an array.
        $keys = [$keys];
      }
      // Convert all elements to strings.
      $keys = array_map('strval', $keys);
      // Filter out boolean operators.
      $keys = array_filter($keys, function ($key) {
        return !in_array($key, ['AND', 'OR', 'NOT']);
      });
      $keys = array_filter(
          $keys,
          [Element::class, 'child'],
          ARRAY_FILTER_USE_KEY
      );
      $this->collector->addEvent('search', [
        'search_term' => implode(' ', $keys),
      ]);
    }

  }

}
