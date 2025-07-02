<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\DataParserPluginManager;

/**
 * Test of the data_parser Json migrate_plus plugin.
 *
 * @group migrate_plus
 */
final class JsonTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_plus'];

  /**
   * Path for the module.
   */
  protected ?string $path;

  /**
   * The plugin manager.
   */
  protected ?DataParserPluginManager $pluginManager = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->path = $this->container->get('module_handler')
      ->getModule('migrate_plus')->getPath();

    $this->pluginManager = $this->container
      ->get('plugin.manager.migrate_plus.data_parser');
  }

  /**
   * Tests missing properties in json file.
   *
   * @param string $file
   *   File name in tests/data/ directory of this module.
   * @param array $ids
   *   Array of ids to pass to the plugin.
   * @param array $fields
   *   Array of fields to pass to the plugin.
   * @param array $expected
   *   Expected array from json decoded file.
   *
   * @dataProvider providerTestMissingProperties
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Exception
   */
  public function testMissingProperties(string $file, array $ids, array $fields, array $expected): void {
    $url = $this->path . '/tests/data/' . $file;

    $conf = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'file',
      'data_parser_plugin' => 'json',
      'destination' => 'node',
      'urls' => [$url],
      'ids' => $ids,
      'fields' => $fields,
      'item_selector' => NULL,
    ];
    $json_parser = $this->pluginManager->createInstance('json', $conf);

    $data = [];
    foreach ($json_parser as $item) {
      $data[] = $item;
    }

    $this->assertEquals($expected, $data);
  }

  /**
   * Provides multiple test cases for the testMissingProperty method.
   *
   * @return array
   *   The test cases.
   */
  public static function providerTestMissingProperties(): array {
    return [
      'missing properties' => [
        'file' => 'missing_properties.json',
        'ids' => ['id' => ['type' => 'integer']],
        'fields' => [
          [
            'name' => 'id',
            'label' => 'Id',
            'selector' => '/id',
          ],
          [
            'name' => 'title',
            'label' => 'Title',
            'selector' => '/title',
          ],
          [
            'name' => 'video_url',
            'label' => 'Video url',
            'selector' => '/video/url',
          ],
        ],
        'expected' => [
          [
            'id' => '1',
            'title' => 'Title',
            'video_url' => 'https://localhost/',
          ],
          [
            'id' => '2',
            'title' => '',
            'video_url' => 'https://localhost/',
          ],
          [
            'id' => '3',
            'title' => 'Title 3',
            'video_url' => '',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests item_selector parser property.
   *
   * @dataProvider providerItemSelector
   */
  public function testItemSelector(mixed $item_selector, array $fields, array $expected): void {
    $url = $this->path . '/tests/data/item_selector.json';

    $conf = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'file',
      'data_parser_plugin' => 'json',
      'destination' => 'node',
      'urls' => [$url],
      'ids' => ['id' => ['type' => 'integer']],
      'item_selector' => $item_selector,
      'fields' => $fields,
    ];

    $json_parser = $this->pluginManager->createInstance('json', $conf);

    $data = [];
    foreach ($json_parser as $item) {
      $data[] = $item;
    }
    $this->assertEquals($expected, $data);
  }

  /**
   * Provides multiple test cases for the testItemSelector method.
   *
   * @return array
   *   The test cases.
   */
  public static function providerItemSelector(): array {
    $fields = [
      [
        'name' => 'id',
        'label' => 'Id',
        'selector' => '/id',
      ],
      [
        'name' => 'title',
        'label' => 'Title',
        'selector' => '/title',
      ],
    ];

    return [
      'item_selector 1st level' => [
        'item_selector' => '/data',
        'fields' => $fields,
        'expected' => [
          [
            'id' => '1',
            'title' => '1 item',
          ],
          [
            'id' => '2',
            'title' => '2 item',
          ],
        ],
      ],
      'item_selector is available, data is empty' => [
        'item_selector' => '/data_empty',
        'fields' => $fields,
        'expected' => [],
      ],
      'item_selector not available' => [
        'item_selector' => '/data_unavailable',
        'fields' => $fields,
        'expected' => [],
      ],
      'item_selector 2nd level' => [
        'item_selector' => '/data/0/items',
        'fields' => $fields,
        'expected' => [
          [
            'id' => '1',
            'title' => '1.1 item',
          ],
          [
            'id' => '2',
            'title' => '1.2 item',
          ],
        ],
      ],
      'item_selector 2nd level, depth selector' => [
        'item_selector' => 3,
        'fields' => $fields,
        'expected' => [
          [
            'id' => '1',
            'title' => '1.1 item',
          ],
          [
            'id' => '2',
            'title' => '1.2 item',
          ],
          [
            'id' => '3',
            'title' => '2.1 item',
          ],
        ],
      ],
    ];
  }

}
