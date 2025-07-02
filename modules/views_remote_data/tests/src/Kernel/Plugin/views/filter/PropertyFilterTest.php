<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views\filter;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Tests\views_remote_data\Kernel\Plugin\views\ViewsPluginTestBase;
use Drupal\views\Plugin\views\display\Embed;
use Drupal\views_remote_data\Plugin\views\filter\PropertyFilter;
use Drupal\views_remote_data\Plugin\views\query\RemoteDataQuery;

/**
 * Tests the property filter plugin.
 *
 * @group remote_views_data
 */
final class PropertyFilterTest extends ViewsPluginTestBase {

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
      ->get('plugin.manager.views.filter')
      ->createInstance('views_remote_data_property');
    self::assertInstanceOf(PropertyFilter::class, $instance);

    $options = [
      'property_path' => 'foobar',
      'value' => 'baz',
      // Default, but pass to ensure test isn't magically assuming the group.
      'group' => 1,
    ];
    $instance->init(
      $view,
      $this->prophesize(Embed::class)->reveal(),
      $options
    );
    $instance->query();

    self::assertCount(1, $query->where);
    self::assertEquals([
      'field' => ['foobar'],
      'value' => 'baz',
      'operator' => '=',
    ], $query->where[1]['conditions'][0]);
  }

}
