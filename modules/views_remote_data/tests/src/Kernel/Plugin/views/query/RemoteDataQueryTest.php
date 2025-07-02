<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views\query;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Tests\views_remote_data\Kernel\Plugin\views\ViewsPluginTestBase;
use Drupal\views\Plugin\views\display\Embed;
use Drupal\views_remote_data\Events\RemoteDataLoadEntitiesEvent;
use Drupal\views_remote_data\Events\RemoteDataQueryEvent;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Tests the query plugin.
 *
 * @group remote_views_data
 */
final class RemoteDataQueryTest extends ViewsPluginTestBase {

  use ProphecyTrait;
  /**
   * Test the plugin.
   */
  public function testPlugin(): void {
    $instance = $this->container
      ->get('plugin.manager.views.query')
      ->createInstance('views_remote_data_query');
    self::assertInstanceOf(RemoteDataQuery::class, $instance);

    $view = $this->createViewExecutable();
    $instance->init($view, $this->prophesize(Embed::class)->reveal());

    $view->query = $instance;

    $instance->build($view);
    $instance->execute($view);

    self::assertCount(2, $this->caughtEvents);

    $event = array_shift($this->caughtEvents);
    self::assertInstanceOf(RemoteDataQueryEvent::class, $event);
    $event = array_shift($this->caughtEvents);
    self::assertInstanceOf(RemoteDataLoadEntitiesEvent::class, $event);
  }

}
