<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLoggerLog;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\process\Get;
use Prophecy\Argument;

/**
 * Tests the migmag_logger_log process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagLoggerLog
 *
 * @group migmag_process
 */
class MigMagLoggerLogTest extends MigrateProcessTestCase {

  /**
   * Default row source ID values.
   *
   * @const array
   */
  const DEFAULT_SOURCE_ID_VALUES = [
    'id' => 'source_row_id',
  ];

  /**
   * Storage for the messages logged during testing.
   *
   * @var array
   */
  protected static $log;

  /**
   * A LoggerChannelInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerChannel;

  /**
   * A MigrationInterface prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerChannel = $this->prophesize(LoggerChannelInterface::class);
    $this->loggerChannel->log(Argument::any(), Argument::type('string'), Argument::type('array'))
      ->will(
        function () {
          [
            $level,
            $message,
            $context,
          ] = func_get_args()[0];

          self::$log = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
          ];
        }
      );

    $this->migration = $this->prophesize(MigrationInterface::class);
    $this->migration->id()->willReturn('test_migration_id');
  }

  /**
   * Tests the transformation of the provided values.
   *
   * @dataProvider providerTestTransform
   * @covers ::transform
   */
  public function testTransform($value, string $expected_log_message, $expected_log_level = RfcLogLevel::INFO, array $plugin_config = [], $source_row_ids = self::DEFAULT_SOURCE_ID_VALUES, $expected_source_id_values = self::DEFAULT_SOURCE_ID_VALUES) {
    self::$log = [];
    $this->row->expects($this->once())
      ->method('getSourceIdValues')
      ->willReturn($source_row_ids);
    $this->plugin = new MigMagLoggerLog(
      $plugin_config,
      'migmag_logger_log',
      [],
      $this->migration->reveal(),
      $this->loggerChannel->reveal()
    );

    $result = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'destination_property');

    // Original value should have been returned without any changes.
    $this->assertEquals($value, $result);
    // Check the arguments sent to the logger.
    $this->assertEquals(
      [
        'message' => $expected_log_message,
        'level' => $expected_log_level,
        'context' => [
          'migration_plugin_id' => 'test_migration_id',
          'source_id_values' => $expected_source_id_values,
        ],
      ],
      self::$log
    );
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestTransform(): array {
    $complex_object = new Get(['plugin_config' => 'value'], 'get', []);
    $expected_object_message = DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      fn () => "Drupal\migrate\Plugin\migrate\process\Get::__set_state(array('pluginId' => 'get', 'pluginDefinition' => array(), 'configuration' => array('plugin_config' => 'value'), 'stringTranslation' => NULL, '_serviceIds' => array(), '_entityStorages' => array(), 'messenger' => NULL, 'stopPipeline' => false, 'multiple' => NULL))",
      fn () => "Drupal\migrate\Plugin\migrate\process\Get::__set_state(array('pluginId' => 'get', 'pluginDefinition' => array(), 'configuration' => array('plugin_config' => 'value'), 'stringTranslation' => NULL, '_serviceIds' => array(), '_entityStorages' => array(), 'messenger' => NULL, 'multiple' => NULL))",
    );
    // With PHP 8.2+, we have a trailing backslash.
    if (version_compare(phpversion(), '8.2.0-dev', 'ge')) {
      $expected_object_message = '\\' . $expected_object_message;
    }
    $simple_object = (object) [
      'key' => 'value',
      'another key' => 'another value',
    ];

    return [
      'null' => [
        'value' => NULL,
        'expected_log_message' => 'NULL',
      ],
      'boolean false' => [
        'value' => FALSE,
        'expected_log_message' => '(boolean) FALSE',
      ],
      'string false' => [
        'value' => 'FALSE',
        'expected_log_message' => "(string) 'FALSE'",
      ],
      'string' => [
        'value' => 'string',
        'expected_log_message' => "(string) 'string'",
      ],
      'simple object' => [
        'value' => $simple_object,
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected_log_message' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => "(object) ['key' => 'value', 'another key' => 'another value']",
          fn () => "(object) array('key' => 'value', 'another key' => 'another value')",
        ),
      ],
      'complex object' => [
        'value' => $complex_object,
        'expected_log_message' => $expected_object_message,
      ],
      'Array' => [
        'value' => [
          'boolean false' => FALSE,
          'boolean true' => TRUE,
          'string' => 'string',
          'null' => NULL,
          'array' => [1, 2],
        ],
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected_log_message' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => "(array) [boolean false => (boolean) FALSE, boolean true => (boolean) TRUE, string => (string) 'string', null => NULL, array => (array) [1, 2]]",
          fn () => "(array) array(boolean false => (boolean) FALSE, boolean true => (boolean) TRUE, string => (string) 'string', null => NULL, array => (array) array(1, 2))",
        ),
      ],
      'Indexed array' => [
        'value' => [
          'string',
          1473635,
          FALSE,
          $simple_object,
        ],
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected_log_message' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => "(array) [(string) 'string', (integer) 1473635, (boolean) FALSE, (object) ['key' => 'value', 'another key' => 'another value']]",
          fn () => "(array) array((string) 'string', (integer) 1473635, (boolean) FALSE, (object) array('key' => 'value', 'another key' => 'another value'))",
        ),
      ],
      'With message' => [
        'value' => 'value',
        'expected_log_message' => "A log message",
        // RfcLogLevel::INFO.
        'expected_log_level' => 6,
        'plugin_config' => [
          'message' => 'A log message',
        ],
      ],
      'With message and args' => [
        'value' => 'value',
        'expected_log_message' => "A log message with args: (string) 'value'",
        'expected_log_level' => 6,
        'plugin_config' => [
          'message' => "A log message with args: %s",
        ],
      ],
      'With message, integer level and args' => [
        'value' => [
          'first value',
          'second value',
        ],
        'expected_log_message' => "A log message with args: (string) 'first value'",
        'expected_log_level' => 1,
        'plugin_config' => [
          'message' => "A log message with args: %s",
          'log_level' => 1,
        ],
      ],
      'With message, string level and args' => [
        'value' => [
          'first value',
          ['second value'],
        ],
        // Starting with core 11.1, Variable::export uses short array syntax.
        'expected_log_message' => DeprecationHelper::backwardsCompatibleCall(
          \Drupal::VERSION,
          '11.1',
          fn () => "A log message with args: (string) 'first value'; (array) ['second value']; missing arg: %s",
          fn () => "A log message with args: (string) 'first value'; (array) array('second value'); missing arg: %s",
        ),
        'expected_log_level' => 'warning',
        'plugin_config' => [
          'message' => "A log message with args: %s; %s; missing arg: %s",
          'log_level' => 'warning',
        ],
        'source_row_ids' => [],
        'expected_source_id_values' => 'No source IDs (maybe a subprocess?)',
      ],
    ];
  }

}
