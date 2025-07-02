<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Events;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Serialization\Json;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\views_remote_data\Kernel\ViewsRemoteDataTestBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;

/**
 * Tests the RemoteDataLoadEntitiesEvent event.
 *
 * @group views_remote_data
 */
final class RemoteDataLoadEntitiesEventTest extends ViewsRemoteDataTestBase {

  use ProphecyTrait;
  /**
   * {@inheritdoc}
   */
  public function onLoadEntities(RemoteDataLoadEntitiesEvent $event): void {
    parent::onLoadEntities($event);

    foreach ($event->getResults() as $result) {
      assert(property_exists($result, 'name'));
      $result->_entity = EntityTest::create([
        'name' => $result->name,
      ]);
    }
  }

  /**
   * Tests the event has results attached.
   */
  public function testEventResults(): void {
    $results = [];
    $fixture = Json::decode((string) file_get_contents(__DIR__ . '/../../../fixtures/simple.json'));
    foreach ($fixture['data'] as $item) {
      $results[] = new ResultRow($item);
    }

    $event = new RemoteDataLoadEntitiesEvent(
      $this->prophesize(ViewExecutable::class)->reveal(),
      $results
    );
    $this->container->get('event_dispatcher')->dispatch($event);
    self::assertCount(1, $this->caughtEvents);
    self::assertInstanceOf(EntityTest::class, $results[0]->_entity);
    self::assertEquals('Llama', $results[0]->_entity->label());
  }

}
