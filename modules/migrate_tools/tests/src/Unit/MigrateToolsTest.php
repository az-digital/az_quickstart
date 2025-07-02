<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Unit;

use Drupal\migrate_tools\MigrateTools;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\migrate_tools\MigrateTools
 * @group migrate_tools
 */
final class MigrateToolsTest extends UnitTestCase {

  /**
   * @covers ::buildIdList
   *
   * @dataProvider dataProviderIdList
   */
  public function testBuildIdList(array $options, array $expected): void {
    $results = MigrateTools::buildIdList($options);
    $this->assertEquals($results, $expected);
  }

  /**
   * Data provider for testBuildIdList.
   */
  public static function dataProviderIdList(): array {
    $cases = [];
    $cases[] = [
      'options' => [],
      'expected' => [],
    ];
    $cases['single id'] = [
      'options' => [
        'idlist' => 123,
      ],
      'expected' => [[123]],
    ];
    $cases['multiple ids'] = [
      'options' => [
        'idlist' => '123, 456',
      ],
      'expected' => [
        [123], [456],
      ],
    ];
    $cases['default delimiter, composite key'] = [
      'options' => [
        'idlist' => '123:456',
      ],
      'expected' => [
        [123, 456],
      ],
    ];
    $cases['special delimiter, single'] = [
      'options' => [
        'idlist' => '123:456',
        'idlist-delimiter' => '~',
      ],
      'expected' => [
        ['123:456'],
      ],
    ];
    $cases['special delimiter, multiple'] = [
      'options' => [
        'idlist' => '123:456~987:654',
        'idlist-delimiter' => '~',
      ],
      'expected' => [
        ['123:456', '987:654'],
      ],
    ];
    $cases['space delimiter, multiple'] = [
      'options' => [
        'idlist' => '123:456 987:654',
        'idlist-delimiter' => ' ',
      ],
      'expected' => [
        ['123:456', '987:654'],
      ],
    ];
    return $cases;
  }

}
