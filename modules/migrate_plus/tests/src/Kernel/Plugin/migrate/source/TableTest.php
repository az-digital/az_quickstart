<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\source;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests Table source plugin.
 *
 * @covers \Drupal\migrate_plus\Plugin\migrate\source\Table
 *
 * @group migrate_plus
 */
final class TableTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_plus'];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * Definition of a test migration.
   */
  protected ?array $migrationDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');

    $this->migrationDefinition = [
      'id' => 'test',
      'source' => [
        'plugin' => 'table',
        'table_name' => 'foo',
        'fields' => [],
        'id_fields' => [],
      ],
      'process' => [],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
  }

  /**
   * Tests 'Table' source plugin requirements.
   */
  public function testCheckRequirements(): void {
    $this->expectException(RequirementsException::class);
    $this->expectExceptionMessage("Source database table 'foo' does not exist");

    $this->migrationPluginManager->createStubMigration($this->migrationDefinition)
      ->getSourcePlugin()
      ->checkRequirements();
  }

  /**
   * Test 'Table' source plugin constructor with invalid configuration.
   *
   * @dataProvider badConfigurationProvider
   */
  public function testTableBadConfiguration(array $configuration, string $message): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $configuration['plugin'] = 'table';
    $this->migrationPluginManager->createStubMigration([
      'source' => $configuration,
    ])->getSourcePlugin();
  }

  /**
   * Data provider with invalid plugin configurations.
   *
   *   Plugin configurations and messages.
   */
  public static function badConfigurationProvider(): array {
    return [
      'Missing table_name' => [
        [],
        'Table plugin is missing table_name property configuration.',
      ],
      'Missing id_fields' => [
        [
          'table_name' => 'foo',
        ],
        'Table plugin is missing id_fields property configuration.',
      ],
      'Configuration id_fields is not an array' => [
        [
          'table_name' => 'foo',
          'id_fields' => 56,
        ],
        'Table plugin configuration property id_fields must be an array.',
      ],
      'Configuration fields is not an array' => [
        [
          'table_name' => 'foo',
          'id_fields' => [
            'color_name' => [
              'type' => 'string',
            ],
          ],
          'fields' => 56,
        ],
        'Table plugin configuration property fields must be an array.',
      ],
    ];
  }

}
