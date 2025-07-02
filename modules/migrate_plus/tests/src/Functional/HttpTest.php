<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\migrate_plus\DataParserPluginManager;

/**
 * Tests the http data_fetcher from the url plugin.
 *
 * @group migrate_plus
 */
final class HttpTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'migrate_plus',
    'migrate_plus_http_test',
  ];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The plugin manager.
   */
  protected ?DataParserPluginManager $pluginManager = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->container->get('plugin.manager.migrate_plus.data_parser');

    $this->user = $this->drupalCreateUser(['administer site configuration', 'access content']);
    $this->drupalLogin($this->user);
  }

  public function testUrlPagerMissingSelector(): void {

    $url = Url::fromRoute('migrate_plus_http_test.json_first', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'urls'
    ];

    $result = $this->getPluginResults($conf);

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
    ];

    $this->assertEquals($expected, $result);
  }

  public function testUrlsPagerMissingSelectorAttribute(){

    $url = Url::fromRoute('migrate_plus_http_test.json_first', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'urls',
      'selector' => 'thisIsMissing'
    ];

    $result = $this->getPluginResults($conf);

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
    ];

    $this->assertEquals($expected, $result);

  }

  public function testUrlsPager(): void {

    $url = Url::fromRoute('migrate_plus_http_test.json_first', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'urls',
      'selector' => 'nextUrl'
    ];

    $result = $this->getPluginResults($conf);

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
    ];

    $this->assertEquals($expected, $result);
  }

  public function testCursorPager(){

    $url = Url::fromRoute('migrate_plus_http_test.json_third', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'cursor',
      'selector' => 'nextPage',
      'key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
      [ "Id" => 7 ],
      [ "Id" => 8 ],
      [ "Id" => 9 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);
  }

  public function testCursorPagerMissingKey(){
    $url = Url::fromRoute('migrate_plus_http_test.json_fourth', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'cursor',
      'selector' => 'nextPage'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
      [ "Id" => 7 ],
      [ "Id" => 8 ],
      [ "Id" => 9 ],
    ];

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'cursor',
      'selector' => 'nextPage'
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);
  }

  public function testCursorPagerMissingSelector(){

    $url = Url::fromRoute('migrate_plus_http_test.json_third', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'cursor',
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);
  }

  public function testCursorPagerMissingSelectorAttribute(){

    $url = Url::fromRoute('migrate_plus_http_test.json_third', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'cursor',
      'selector' => 'missingAttribute'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);
  }

  public function testPagePager(){

    $url = Url::fromRoute('migrate_plus_http_test.json_third', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'page',
      'selector' => 'currentPage',
      'key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);

  }

  public function testPagePagerSelectorMax(){

    $url = Url::fromRoute('migrate_plus_http_test.json_third', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'page',
      'selector' => 'currentPage',
      'selector_max' => 'numPages',
      'key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);

  }

  public function testPaginationPagerNumItemsSelector(){

    $url = Url::fromRoute('migrate_plus_http_test.json_fifth', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'paginator',
      'selector' => 'numItems',
      'default_num_items' => 3,
      'page_key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);

  }

  public function testPaginationPagerRowArraySelector(){

    $url = Url::fromRoute('migrate_plus_http_test.json_fifth', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'paginator',
      'selector' => 'data',
      'default_num_items' => 3,
      'page_key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);

  }

  public function testPaginationPagerNoSelector(){

    $url = Url::fromRoute('migrate_plus_http_test.json_fifth', [], ['absolute' => TRUE]);

    $conf = $this->getBaseConfiguration();
    $conf['urls'][] = $url->toString();
    $conf['pager'] = [
      'type' => 'paginator',
      'default_num_items' => 3,
      'page_key' => 'page'
    ];

    $expected = [
      [ "Id" => 1 ],
      [ "Id" => 2 ],
      [ "Id" => 3 ],
      [ "Id" => 4 ],
      [ "Id" => 5 ],
      [ "Id" => 6 ],
    ];

    $result = $this->getPluginResults($conf);
    $this->assertEquals( $expected, $result);
  }

  protected function getBaseConfiguration( ): array{
    return [
      'plugin' => 'url',
      'data_fetcher_plugin' => 'http',
      'data_parser_plugin' => 'json',
      'pager' => [],
      'urls' => [],
      'ids' => [
        'Id' => [
          'type' => 'integer'
        ]
      ],
      'fields' => [
        [
          'name' => 'Id',
          'label' => 'ID',
          'selector' => '/id'
        ],
      ],
      'item_selector' => 'data',
    ];
  }

  protected function getPluginResults( array $configuration ): array{
    $json_parser = $this->pluginManager->createInstance('json', $configuration);
    $data = [];
    foreach ($json_parser as $item) {
      $data[] = $item;
    }
    return $data;
  }

}
