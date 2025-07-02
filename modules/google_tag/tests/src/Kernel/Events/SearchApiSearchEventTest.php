<?php

declare(strict_types=1);

namespace Drupal\Tests\google_tag\Kernel\Events;

use Drupal\google_tag\Entity\TagContainer;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\google_tag\Kernel\GoogleTagTestCase;

/**
 * Search Api search event test.
 *
 * @group google_tag
 */
final class SearchApiSearchEventTest extends GoogleTagTestCase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'search_api',
    'search_api_db',
    'search_api_test_db',
  ];

  /**
   * Tests search event.
   */
  public function testEvent(): void {
    $this->installSchema('search_api', ['search_api_item']);
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installEntitySchema('search_api_task');
    $this->installConfig(['search_api', 'search_api_test_db']);

    TagContainer::create([
      'id' => 'foo',
      'weight' => 10,
      'events' => ['search' => []],
    ])->save();

    $index = Index::load('database_search_index');
    $query = $index->query();
    $query->keys('foo bar baz');
    $query->execute();

    $collector = $this->container->get('google_tag.event_collector');
    $events = $collector->getEvents();
    self::assertCount(1, $events);
    self::assertEquals('search', $events[0]->getName());
    self::assertEquals([
      'search_term' => 'foo bar baz',
    ], $events[0]->getData());
  }

}
