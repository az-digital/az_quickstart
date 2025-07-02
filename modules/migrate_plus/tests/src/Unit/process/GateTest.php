<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\Gate;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the gate process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\Gate
 */
final class GateTest extends MigrateProcessTestCase {

  /**
   * Test Gate plugin.
   *
   * @dataProvider gateProvider
   */
  public function testGate($row_data, $destination_data, $configuration, $message): void {
    $row = new Row($row_data);
    if (!empty($destination_data)) {
      foreach ($destination_data as $key => $val) {
        $row->setDestinationProperty($key, $val);
      }
    }
    if (!empty($message)) {
      $this->expectException(MigrateSkipProcessException::class);
      $this->expectExceptionMessage($message);
    }
    $plugin = new Gate($configuration, 'gate', []);
    $value = $row_data[$configuration['source']];
    $transformed = $plugin->transform($value, $this->migrateExecutable, $row, 'destinationproperty');
    if (empty($message)) {
      $this->assertSame($value, $transformed);
    }
  }

  /**
   * Row and plugin configuration for tests.
   */
  public static function gateProvider(): array {
    return [
      'Gate does not unlock' => [
        [
          'state_abbr' => 'MO',
          'source_data' => 'Let me through!',
        ],
        NULL,
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'unlock',
        ],
        'Processing of destination property destinationproperty was skipped: Gate was not unlocked by property state_abbr with value MO.',
      ],
      'Gate unlocks (with valid_keys array)' => [
        [
          'state_abbr' => 'Colorado',
          'source_data' => 'Let me through!',
        ],
        NULL,
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => ['CO', 'Colorado'],
          'key_direction' => 'unlock',
        ],
        NULL,
      ],
      'Gate locks' => [
        [
          'state_abbr' => 'CO',
          'source_data' => 'Let me through!',
        ],
        NULL,
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'lock',
        ],
        'Processing of destination property destinationproperty was skipped: Gate was locked by property state_abbr with value CO.',
      ],
      'Gate stays unlocked' => [
        [
          'state_abbr' => 'MO',
          'source_data' => 'Let me through!',
        ],
        NULL,
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'lock',
        ],
        NULL,
      ],
      'Destination prop does not unlock gate' => [
        ['source_data' => 'Let me through!'],
        ['state_abbr' => 'MO'],
        [
          'source' => 'source_data',
          'use_as_key' => '@state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'unlock',
        ],
        'Processing of destination property destinationproperty was skipped: Gate was not unlocked by property @state_abbr with value MO.',
      ],
      'Destination prop unlocks gate' => [
        ['source_data' => 'Let me through!'],
        ['state_abbr' => 'CO'],
        [
          'source' => 'source_data',
          'use_as_key' => '@state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'unlock',
        ],
        NULL,
      ],
      'Destination prop locks gate' => [
        ['source_data' => 'Let me through!'],
        ['state_abbr' => 'CO'],
        [
          'source' => 'source_data',
          'use_as_key' => '@state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'lock',
        ],
        'Processing of destination property destinationproperty was skipped: Gate was locked by property @state_abbr with value CO.',
      ],
      'Gate stays unlocked with destination prop' => [
        ['source_data' => 'Let me through!'],
        ['state_abbr' => 'MO'],
        [
          'source' => 'source_data',
          'use_as_key' => '@state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'lock',
        ],
        NULL,
      ],
    ];
  }

  /**
   * Test Gate plugin with bad configuration.
   *
   * @dataProvider badConfigurationProvider
   */
  public function testGateBadConfiguration($configuration, string $message): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    new Gate($configuration, 'gate', []);
  }

  /**
   * Provider for bad configuration.
   */
  public static function badConfigurationProvider(): array {
    return [
      'Missing use_as_key' => [
        [
          'source' => 'source_data',
          'valid_keys' => 'CO',
          'key_direction' => 'unlock',
        ],
        'Gate plugin is missing use_as_key configuration.',
      ],
      'Missing valid_keys' => [
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'key_direction' => 'unlock',
        ],
        'Gate plugin is missing valid_keys configuration.',
      ],
      'Missing key_direction' => [
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => 'CO',
        ],
        'Gate plugin is missing key_direction configuration.',
      ],
      'Invalid key_direction' => [
        [
          'source' => 'source_data',
          'use_as_key' => 'state_abbr',
          'valid_keys' => 'CO',
          'key_direction' => 'open',
        ],
        'Gate plugin only accepts the following values for key_direction: lock and unlock.',
      ],
    ];
  }

}
