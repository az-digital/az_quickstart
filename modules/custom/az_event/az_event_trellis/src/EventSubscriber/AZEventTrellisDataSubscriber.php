<?php

namespace Drupal\az_event_trellis\EventSubscriber;

use Drupal\az_event_trellis\TrellisHelper;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\Core\Messenger\Messenger;

/**
 * Provides API integration for Trellis Views.
 */
final class AZEventTrellisDataSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * @var \Drupal\az_event_trellis\TrellisHelper
   */
  protected $trellisHelper;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RemoteDataQueryEvent::class => 'onQuery',
      MigrateEvents::POST_ROW_SAVE => 'onPostRowSave',
    ];
  }

  /**
   * Constructs an AZEventTrellisDataSubscriber.
   *
   * @param \Drupal\az_event_trellis\TrellisHelper $trellisHelper
   *   The Trellis helper server.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Database connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(TrellisHelper $trellisHelper, Messenger $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->trellisHelper = $trellisHelper;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
  }

  /**
   * Respond to events on migration import for relevant migrations.
   *
   * @param \Drupal\migrate\Event\MigratePostRowSaveEvent $event
   *   The post save event object.
   */
  public function onPostRowSave(MigratePostRowSaveEvent $event) {
    $migration = $event->getMigration()->getBaseId();
    $ids = $event->getDestinationIdValues();
    $id = reset($ids);
    if ($migration === 'az_trellis_events') {
      $event = $this->nodeStorage->load($id);
      $url = $event->toUrl()->toString();
      if (!empty($event)) {
        $this->messenger->addMessage(t('Imported <a href="@eventlink">@eventtitle</a>.', [
          '@eventlink' => $url,
          '@eventtitle' => $event->getTitle(),
        ]));
      }
    }
  }

  /**
   * Subscribes to populate Trellis view results.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataQueryEvent $event
   *   The event.
   */
  public function onQuery(RemoteDataQueryEvent $event): void {
    $supported_bases = ['az_event_trellis_data'];
    $base_tables = array_keys($event->getView()->getBaseTables());
    if (count(array_intersect($supported_bases, $base_tables)) > 0) {
      $parameters = [];
      $condition_groups = $event->getConditions();
      // Check for conditional parameters.
      foreach ($condition_groups as $condition_group) {
        if (!empty($condition_group['conditions'])) {
          foreach ($condition_group['conditions'] as $condition) {
            if (!empty($condition['field'][0]) & !empty($condition['value'])) {
              $parameters[$condition['field'][0]] = $condition['value'];
            }
          }
        }
      }
      if (empty($parameters)) {
        return;
      }
      $ids = $this->trellisHelper->searchEvents($parameters);
      if (!empty($ids)) {
        $offset = $event->getOffset();
        $limit = $event->getLimit();
        if (!empty($limit)) {
          $ids = array_slice($ids, $offset, $limit);
        }
        // Run data fetch request.
        $results = $this->trellisHelper->getEvents($ids);
        $datefields = [
          'Last_Modified_Date',
          'Start_DateTime',
          'End_DateTime',
        ];
        foreach ($results as $result) {
          // Change date format fields to what views expects to see.
          foreach ($datefields as $datefield) {
            if (!empty($result[$datefield])) {
              $result[$datefield] = strtotime($result[$datefield]);
            }
          }
          $event->addResult(new ResultRow($result));
        }
      }
    }
  }

}
