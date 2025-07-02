<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_parser;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;

/**
 * Test of the data_parser SimpleXml migrate_plus plugin.
 *
 * @group migrate_plus
 */
abstract class BaseXml extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_plus'];

  /**
   * Path for the xml file.
   */
  protected ?string $path;

  /**
   * The plugin manager.
   */
  protected ?DataParserPluginManager $pluginManager = NULL;

  /**
   * The plugin configuration.
   */
  protected ?array $configuration;

  /**
   * The expected result.
   */
  protected ?array $expected;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->path = $this->container->get('module_handler')
      ->getModule('migrate_plus')->getPath();
    $this->pluginManager = $this->container
      ->get('plugin.manager.migrate_plus.data_parser');
    $this->configuration = [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'file',
      'data_parser_plugin' => 'simple_xml',
      'destination' => 'node',
      'urls' => [],
      'ids' => ['id' => ['type' => 'integer']],
      'fields' => [
        [
          'name' => 'id',
          'label' => 'Id',
          'selector' => '@id',
        ],
        [
          'name' => 'values',
          'label' => 'Values',
          'selector' => 'values',
        ],
      ],
      'item_selector' => '/items/item',
    ];
    $this->expected = [
      [
        'Value 1',
        'Value 2',
      ],
      [
        'Value 1 (single)',
      ],
    ];
  }

  /**
   * Tests current URL of parsed XML item.
   */
  public function testCurrentUrl(): void {
    $urls = [
      $this->path . '/tests/data/xml_current_url1.xml',
      $this->path . '/tests/data/xml_current_url2.xml',
    ];
    $this->configuration['urls'] = $urls;
    $parser = $this->getParser();

    // First 2 items available in the first URL.
    $parser->rewind();
    $this->assertEquals($urls[0], $parser->currentUrl());
    $parser->next();
    $this->assertEquals($urls[0], $parser->currentUrl());

    // Third item available in the second URL.
    $parser->next();
    $this->assertEquals($urls[1], $parser->currentUrl());
  }

  /**
   * Tests reducing single values.
   */
  public function testReduceSingleValue(): void {
    $url = $this->path . '/tests/data/xml_reduce_single_value.xml';
    $this->configuration['urls'][0] = $url;
    $this->assertResults($this->expected, $this->getParser());
  }

  /**
   * Tests retrieving single value from element with attributes.
   */
  public function testSingleValueWithAttributes() {
    $urls = [
      $this->path . '/tests/data/xml_persons.xml',
    ];
    $this->configuration['urls'] = $urls;
    $this->configuration['item_selector'] = '/persons/person';
    $this->configuration['fields'] = [
      [
        'name' => 'id',
        'label' => 'Id',
        'selector' => 'id',
      ],
      [
        'name' => 'child',
        'label' => 'child',
        'selector' => 'children/child',
      ],
    ];

    $names = [];
    foreach ($this->getParser() as $item) {
      $names[] = (string) $item['child']->name;
    }

    $expected_names = ['Elizabeth Junior', 'George Junior', 'Lucy'];
    $this->assertEquals($expected_names, $names);
  }

  /**
   * Tests retrieval a value with multiple items.
   */
  public function testMultipleItems(): void {
    $this->configuration['urls'] = [
      $this->path . '/tests/data/xml_multiple_items.xml',
    ];
    $this->configuration['fields'] = [
      [
        'name' => 'id',
        'label' => 'Id',
        'selector' => 'Id',
      ],
      [
        'name' => 'sub_items1',
        'label' => 'Sub items 1',
        'selector' => 'Values1/SubItem',
      ],
      [
        'name' => 'sub_items2',
        'label' => 'Sub items 2',
        'selector' => 'Values2/SubItem',
      ],
    ];

    $parser = $this->getParser();
    $parser->next();

    // Transform SimpleXMLELements to arrays.
    $item = json_decode(json_encode($parser->current()), TRUE);
    $sub_items1 = array_column($item['sub_items1'], 'Id');
    $this->assertEquals(['1', '2'], $sub_items1);
    $this->assertEquals(['3', '4'], $item['sub_items2']);
  }

  /**
   * Parses and asserts the results match expectations.
   *
   * @param array|string $expected
   *   The expected results.
   * @param \Traversable $parser
   *   An iterable data result to parse.
   */
  protected function assertResults($expected, \Traversable $parser): void {
    $data = [];
    foreach ($parser as $item) {
      $values = [];
      foreach ($item['values'] as $value) {
        $values[] = (string) $value;
      }
      $data[] = $values;
    }
    $this->assertEquals($expected, $data);
  }

  /**
   * Returns a parse object with active configuration.
   *
   * @return \Drupal\migrate_plus\DataParserPluginInterface
   *   Data parser object.
   */
  abstract protected function getParser(): DataParserPluginInterface;

}
