<?php

namespace Drupal\az_event_trellis\EventSubscriber;

use Drupal\views\ResultRow;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\Core\Messenger\Messenger;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides API integration for Trellis Views.
 */
final class AZEventTrellisDataSubscriber implements EventSubscriberInterface {

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

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
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Database connection object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(ClientInterface $httpClient, Messenger $messenger, EntityTypeManagerInterface $entityTypeManager) {
    $this->httpClient = $httpClient;
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
    // @todo replace with actual API endpoint.
    $api_gateway = 'https://api.dev.trellis.arizona.edu/';
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
      try {
        $url = $api_gateway . 'ws/rest/eventsapi/v1/searchevents/';
        // Run search request.
        $response = $this->httpClient->request('GET', $url, ['query' => $parameters]);
        if ($response->getStatusCode() === 200) {
          $json = (string) $response->getBody();
          $json = json_decode($json, TRUE);
          if ($json !== NULL) {
            $ids = $json['data']['Event_IDs'] ?? [];
            if (!empty($ids)) {
              $offset = $event->getOffset();
              $limit = $event->getLimit();
              if (!empty($limit)) {
                $ids = array_slice($ids, $offset, $limit);
              }
              // Run data fetch request.
              $url = $api_gateway . 'ws/rest/getevents/v2/eventinfo/';
              $data = ['ids' => implode(',', $ids)];
              $response = $this->httpClient->request('POST', $url, ['json' => $data]);
              if ($response->getStatusCode() === 200) {
                $json = (string) $response->getBody();
                $json = json_decode($json, TRUE);
                if ($json !== NULL) {
                  $results = $json['data'] ?? [];
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
        }
      }
      catch (GuzzleException $e) {
      }
    }
  }

}
