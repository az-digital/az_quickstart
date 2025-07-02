<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Events;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Tests\views_remote_data\Kernel\ViewsRemoteDataTestBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;

/**
 * Tests the RemoteDataQueryEvent event.
 *
 * @group views_remote_data
 */
final class RemoteDataQueryEventTest extends ViewsRemoteDataTestBase {

  use ProphecyTrait;
  /**
   * {@inheritdoc}
   */
  public function onQuery(RemoteDataQueryEvent $event): void {
    parent::onQuery($event);

    $fixture = Json::decode((string) file_get_contents(__DIR__ . '/../../../fixtures/simple.json'));
    foreach ($fixture['data'] as $item) {
      $event->addResult(new ResultRow($item));
    }
  }

  /**
   * Tests the event has results attached.
   */
  public function testEventResults(): void {
    $event = new RemoteDataQueryEvent(
      $this->prophesize(ViewExecutable::class)->reveal(),
      [],
      [],
      0,
      10
    );
    $this->container->get('event_dispatcher')->dispatch($event);
    self::assertCount(1, $this->caughtEvents);
    $results = $event->getResults();
    self::assertCount(2, $results);
    self::assertObjectHasProperty('name', $results[0]);
    self::assertEquals('Llama', $results[0]->name);
  }

}
