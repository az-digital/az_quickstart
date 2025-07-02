<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate_plus\data_fetcher;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;

/**
 * Class HttpTest.
 *
 * @group migrate_plus
 * @package Drupal\Tests\migrate_plus\Unit\migrate_plus\data_fetcher
 */
final class HttpTest extends KernelTestBase {

  /**
   * Test http headers option.
   *
   * @dataProvider headerDataProvider
   */
  public function testHttpHeaders(array $definition, array $expected, array $preSeed = []): void {
    $http = new Http($definition, 'http', []);
    $this->assertEquals($expected, $http->getRequestHeaders());
  }

  /**
   * Provides multiple test cases for the testHttpHeaders method.
   *
   * @return array
   *   The test cases
   */
  public static function headerDataProvider(): array {
    return [
      'dummy headers specified' => [
        'definition' => [
          'headers' => [
            'Accept' => 'application/json',
            'User-Agent' => 'Internet Explorer 6',
            'Authorization-Key' => 'secret',
            'Arbitrary-Header' => 'foobarbaz',
          ],
        ],
        'expected' => [
          'Accept' => 'application/json',
          'User-Agent' => 'Internet Explorer 6',
          'Authorization-Key' => 'secret',
          'Arbitrary-Header' => 'foobarbaz',
        ],
      ],
      'no headers specified' => [
        'definition' => [
          'no_headers_here' => 'foo',
        ],
        'expected' => [],
      ],
    ];
  }

}
