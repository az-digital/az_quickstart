<?php

namespace Drupal\Tests\paragraphs\Unit\migrate;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\paragraphs\MigrationPluginsAlterer;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the MigrationPluginsAlterer service.
 *
 * @todo Cover every method.
 *
 * @coversDefaultClass \Drupal\paragraphs\MigrationPluginsAlterer
 *
 * @group paragraphs
 */
class MigrationPluginsAltererTest extends UnitTestCase {

  /**
   * The migration plugin alterer.
   *
   * @var \Drupal\paragraphs\MigrationPluginsAlterer
   */
  protected $paragraphsMigrationPluginsAlterer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $logger_channel = $this->createMock('Drupal\Core\Logger\LoggerChannelInterface');
    $logger_factory = $this->createMock('Drupal\Core\Logger\LoggerChannelFactoryInterface');
    $logger_factory->expects($this->atLeastOnce())
      ->method('get')
      ->with('paragraphs')
      ->willReturn($logger_channel);

    $this->paragraphsMigrationPluginsAlterer = new MigrationPluginsAlterer($logger_factory);
  }

  /**
   * Tests that migration processes are transformed to an array of processors.
   *
   * @dataProvider providerParagraphsMigrationPrepareProcess
   * @covers ::paragraphsMigrationPrepareProcess
   */
  public function testParagraphsMigrationPrepareProcess(array $input, array $expected) {
    ['process' => $process, 'property' => $property] = $input;
    $success = $this->paragraphsMigrationPluginsAlterer->paragraphsMigrationPrepareProcess($process, $property);
    $this->assertSame($expected['return'], $success);
    $this->assertEquals($expected['process'], $process);
  }

  /**
   * Provides data and expected results for testing the prepare process method.
   *
   * @return array[]
   *   Data and expected results.
   */
  public static function providerParagraphsMigrationPrepareProcess() {
    return [
      // Missing property (no change).
      [
        'input' => [
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
          'property' => 'missing',
        ],
        'expected' => [
          'return' => FALSE,
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
        ],
      ],
      // Existing string property.
      [
        'input' => [
          'process' => [
            'catname' => 'Picurka',
            'wont/touch' => 'this',
          ],
          'property' => 'catname',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'catname' => [
              [
                'plugin' => 'get',
                'source' => 'Picurka',
              ],
            ],
            'wont/touch' => 'this',
          ],
        ],
      ],
      // Single process plugin.
      [
        'input' => [
          'process' => [
            'cat' => [
              'plugin' => 'migration_lookup',
              'migration' => 'cats',
              'source' => 'cat_id',
            ],
          ],
          'property' => 'cat',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'cat' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
            ],
          ],
        ],
      ],
      // Array of process plugins (no change).
      [
        'input' => [
          'process' => [
            'catname' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
              [
                'plugin' => 'extract',
                'index' => ['name'],
              ],
              [
                'plugin' => 'callback',
                'callable' => 'ucfirst',
              ],
            ],
          ],
          'property' => 'catname',
        ],
        'expected' => [
          'return' => TRUE,
          'process' => [
            'catname' => [
              [
                'plugin' => 'migration_lookup',
                'migration' => 'cats',
                'source' => 'cat_id',
              ],
              [
                'plugin' => 'extract',
                'index' => ['name'],
              ],
              [
                'plugin' => 'callback',
                'callable' => 'ucfirst',
              ],
            ],
          ],
        ],
      ],
      // Invalid type.
      [
        'input' => [
          'process' => [
            'invalid' => (object) [
              [
                'not a' => 'kitten',
              ],
            ],
          ],
          'property' => 'invalid',
        ],
        'expected' => [
          'return' => FALSE,
          'process' => [
            'invalid' => (object) [
              [
                'not a' => 'kitten',
              ],
            ],
          ],
        ],
      ],
    ];
  }

}
