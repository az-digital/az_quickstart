<?php

declare(strict_types=1);

namespace Drupal\views_remote_data_test\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Test subscriber for populating values in test views.
 */
final class ViewsRemoteDataSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      RemoteDataQueryEvent::class => 'onQuery',
      RemoteDataLoadEntitiesEvent::class => 'onLoadEntities',
    ];
  }

  /**
   * Subscribes to populate entities against the results.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent $event
   *   The event.
   *
   * @todo need tests which test this.
   */
  public function onLoadEntities(RemoteDataLoadEntitiesEvent $event): void {
    $supported_bases = [
      'views_remote_data_test_entity_test',
    ];
    $base_tables = array_keys($event->getView()->getBaseTables());
    if (count(array_intersect($supported_bases, $base_tables)) > 0) {
      foreach ($event->getResults() as $key => $result) {
        assert(property_exists($result, 'name'));
        // Set the entity ID to verify tags bubble to the query's cache tags.
        $result->_entity = EntityTest::create([
          'id' => $key + 1,
          'name' => $result->name,
        ]);
        $result->_entity->setOriginalId($result->_entity->id());
      }
    }
  }

  /**
   * Subscribes to populate the view results.
   *
   * @param \Drupal\views_remote_data\Events\RemoteDataQueryEvent $event
   *   The event.
   */
  public function onQuery(RemoteDataQueryEvent $event): void {
    $supported_bases = [
      'views_remote_data_test_simple',
      'views_remote_data_test_entity_test',
    ];
    $base_tables = array_keys($event->getView()->getBaseTables());
    if (count(array_intersect($supported_bases, $base_tables)) > 0) {
      // Ensure cache tags can be bubbled.
      $event->addCacheTags(['test_additional_cache_tag']);

      $fixture = Json::decode((string) file_get_contents(__DIR__ . '/../../../../fixtures/simple.json'));
      $conditions = $event->getConditions();
      foreach ($fixture['data'] as $item) {
        foreach ($conditions as $group) {
          foreach ($group['conditions'] as $group_condition) {
            $value = NestedArray::getValue($item, $group_condition['field']);
            if ($group_condition['operator'] === '=') {
              if ($value !== $group_condition['value']) {
                continue 3;
              }
            }
            elseif ($group_condition['operator'] === '!=') {
              if ($value === $group_condition['value']) {
                continue 3;
              }
            }
            else {
              continue 3;
            }
          }
        }
        $event->addResult(new ResultRow($item));

      }
    }
  }

}
