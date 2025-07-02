<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views\sort;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Tests\views_remote_data\Kernel\Plugin\views\ViewsPluginTestBase;
use Drupal\views\Plugin\views\display\Embed;
use Drupal\views_remote_data\Plugin\views\sort\PropertySort;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Tests the property sort plugin.
 *
 * @group remote_views_data
 */
final class PropertySortTest extends ViewsPluginTestBase {

  use ProphecyTrait;
  /**
   * Tests the plugin.
   */
  public function testPlugin(): void {
    $view = $this->createViewExecutable();

    $query = $this->container
      ->get('plugin.manager.views.query')
      ->createInstance('views_remote_data_query');
    self::assertInstanceOf(RemoteDataQuery::class, $query);
    $view->query = $query;

    $instance = $this->container
      ->get('plugin.manager.views.sort')
      ->createInstance('views_remote_data_property');
    self::assertInstanceOf(PropertySort::class, $instance);

    $options = [
      'property_path' => 'foobar',
      'order' => 'DESC',
    ];
    $instance->init(
      $view,
      $this->prophesize(Embed::class)->reveal(),
      $options
    );
    $instance->query();

    self::assertCount(1, $query->orderby);
    self::assertEquals([
      'field' => ['foobar'],
      'order' => 'DESC',
    ], $query->orderby[0]);
  }

}
