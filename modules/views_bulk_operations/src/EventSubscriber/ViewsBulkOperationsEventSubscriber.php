<?php

namespace Drupal\views_bulk_operations\EventSubscriber;

use Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface;
use Drupal\views_bulk_operations\ViewsBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Defines module event subscriber class.
 *
 * Allows getting data of core entity views.
 */
class ViewsBulkOperationsEventSubscriber implements EventSubscriberInterface {

  // Subscribe to the VBO event with high priority
  // to prepopulate the event data.
  private const PRIORITY = 999;

  /**
   * Object constructor.
   *
   * @param \Drupal\views_bulk_operations\Service\ViewsBulkOperationsViewDataInterface $viewData
   *   The VBO View Data provider service.
   */
  public function __construct(
    protected readonly ViewsBulkOperationsViewDataInterface $viewData
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ViewsBulkOperationsEvent::NAME][] = [
      'provideViewData',
      self::PRIORITY,
    ];
    return $events;
  }

  /**
   * Respond to view data request event.
   *
   * @var \Drupal\views_bulk_operations\ViewsBulkOperationsEvent $event
   *   The event to respond to.
   */
  public function provideViewData(ViewsBulkOperationsEvent $event): void {
    $view_data = $event->getViewData();
    if (!empty($view_data['table']['entity type'])) {
      $event->setEntityTypeIds([$view_data['table']['entity type']]);
      $event->setEntityGetter([
        'callable' => [$this->viewData, 'getEntityDefault'],
      ]);
    }
  }

}
