<?php

declare(strict_types=1);

namespace Drupal\Tests\views_remote_data\Kernel\Plugin\views\field;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Tests\views_remote_data\Kernel\Plugin\views\ViewsPluginTestBase;
use Drupal\views\Plugin\views\display\Embed;
use Drupal\views\ResultRow;
use Drupal\views_remote_data\Plugin\views\field\PropertyField;

/**
 * Tests the property field plugin.
 *
 * @group remote_views_data
 */
final class PropertyFieldTest extends ViewsPluginTestBase {

  use ProphecyTrait;
  /**
   * Tests the plugin.
   */
  public function testPlugin(): void {
    $view = $this->createViewExecutable();

    $instance = $this->container
      ->get('plugin.manager.views.field')
      ->createInstance('views_remote_data_property');
    self::assertInstanceOf(PropertyField::class, $instance);

    $result = new ResultRow([
      'name' => 'foobar',
      'baz' => [
        'raz' => 'matazz',
      ],
    ]);

    $options = [
      'property_path' => 'name',
    ];
    $instance->init($view, $this->prophesize(Embed::class)->reveal(), $options);
    self::assertEquals('foobar', $instance->getValue($result));

    $options = [
      'property_path' => 'baz.raz',
    ];
    $instance->init($view, $this->prophesize(Embed::class)->reveal(), $options);
    self::assertEquals('matazz', $instance->getValue($result));

    $options = [
      'property_path' => 'name.raz',
    ];
    $instance->init($view, $this->prophesize(Embed::class)->reveal(), $options);
    self::assertNull($instance->getValue($result));

    $options = [
      'property_path' => 'baz.0.raz',
    ];
    $instance->init($view, $this->prophesize(Embed::class)->reveal(), $options);
    self::assertNull($instance->getValue($result));
  }

}
