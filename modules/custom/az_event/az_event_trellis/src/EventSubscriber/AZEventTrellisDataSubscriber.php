<?php

namespace Drupal\az_event_trellis\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountProxy;
use Drupal\az_event_trellis\TrellisHelper;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Provides API integration for Trellis Views.
 */
final class AZEventTrellisDataSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

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
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

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
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   The currently logged in user.
   * @param \Drupal\Core\Queue\QueueFactory $queueFactory
   *   The queue factory.
   */
  public function __construct(TrellisHelper $trellisHelper, Messenger $messenger, EntityTypeManagerInterface $entityTypeManager, AccountProxy $currentUser, QueueFactory $queueFactory) {
    $this->trellisHelper = $trellisHelper;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entityTypeManager;
    $this->nodeStorage = $this->entityTypeManager->getStorage('node');
    $this->currentUser = $currentUser;
    $this->queueFactory = $queueFactory;
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
      $image = $event->getRow()->get('image_url');
      $event = $this->nodeStorage->load($id);
      if (!empty($event)) {
        if (!empty($image)) {
          // Defer download of image until later.
          $job = [
            'id' => $id,
            'id_field' => 'nid',
            'entity_type' => 'node',
            'media_type' => 'az_image',
            'media_field' => 'field_az_photos',
            'file_field' => 'field_media_az_image',
            'filename' => 'trellis_event',
            'url' => $image,
            'alt' => $event->getTitle(),
          ];
          $this->queueFactory->get('az_deferred_media')->createItem($job);
        }

        $url = $event->toUrl()->toString();
        // Only show message if current user has permission.
        if ($this->currentUser->hasPermission('create az_event content')) {
          // Show status message that event was imported.
          $this->messenger->addMessage(t('Imported <a href="@eventlink">@eventtitle</a>.', [
            '@eventlink' => $url,
            '@eventtitle' => $event->getTitle(),
          ]));
        }
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
      // Don't perform search if empty or publish is the only field.
      if (empty($parameters) || (count($parameters) <= 1)) {
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
