<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Html;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\process\DomMigrationLookup;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the dom_migration_lookup process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DomMigrationLookup
 */
final class DomMigrationLookupTest extends MigrateProcessTestCase {

  /**
   * Example configuration for the dom_migration_lookup process plugin.
   *
   * @var array
   */
  protected $exampleConfiguration = [
    'plugin' => 'dom_migration_lookup',
    'mode' => 'attribute',
    'xpath' => '//a',
    'attribute_options' => [
      'name' => 'href',
    ],
    'search' => '@/user/(\d+)@',
    'replace' => '/user/[mapped-id]',
    'migrations' => [
      'users' => [],
      'people' => [
        'replace' => '/people/[mapped-id]',
      ],
    ],
  ];

  /**
   * Mock a migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected object $migration;

  /**
   * Mock a process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected object $processPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock a  migration.
    $prophecy = $this->prophesize(MigrationInterface::class);
    $this->migration = $prophecy->reveal();

    // Mock two migration lookup plugins.
    $prophecy = $this->prophesize(MigrateProcessInterface::class);
    $prophecy
      ->transform('123', $this->migrateExecutable, $this->row, 'destinationproperty')
      ->willReturn('321');
    $prophecy
      ->transform('456', $this->migrateExecutable, $this->row, 'destinationproperty')
      ->willReturn(NULL);
    $users_lookup_plugin = $prophecy->reveal();
    $prophecy = $this->prophesize(MigrateProcessInterface::class);
    $prophecy
      ->transform('123', $this->migrateExecutable, $this->row, 'destinationproperty')
      ->willReturn('ignored');
    $prophecy
      ->transform('456', $this->migrateExecutable, $this->row, 'destinationproperty')
      ->willReturn('654');
    $people_lookup_plugin = $prophecy->reveal();

    // Mock a process plugin manager.
    $prophecy = $this->prophesize(MigratePluginManager::class);
    $users_configuration = [
      'migration' => 'users',
      'no_stub' => TRUE,
    ];
    $people_configuration = [
      'migration' => 'people',
      'no_stub' => TRUE,
    ];
    $prophecy
      ->createInstance('migration_lookup', $users_configuration, $this->migration)
      ->willReturn($users_lookup_plugin);
    $prophecy
      ->createInstance('migration_lookup', $people_configuration, $this->migration)
      ->willReturn($people_lookup_plugin);
    $this->processPluginManager = $prophecy->reveal();
  }

  /**
   * @covers ::__construct
   *
   * @dataProvider providerTestConfigValidation
   */
  public function testConfigValidation(array $config_overrides, string $message): void {
    $configuration = $config_overrides + $this->exampleConfiguration;
    $value = '<p>A simple paragraph.</p>';
    $this->expectException(InvalidPluginDefinitionException::class);
    $this->expectExceptionMessage($message);
    (new DomMigrationLookup($configuration, 'dom_migration_lookup', [], $this->migration, $this->processPluginManager))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Dataprovider for testConfigValidation().
   */
  public static function providerTestConfigValidation(): array {
    $cases = [
      'migrations-empty' => [
        ['migrations' => []],
        "Configuration option 'migration' is required.",
      ],
      'migrations-invalid' => [
        ['migrations' => 42],
        "Configuration option 'migration' should be a keyed array.",
      ],
      'replace-null' => [
        ['replace' => NULL],
        "Please define either a global replace for all migrations, or a specific one for 'migrations.users'.",
      ],
    ];

    return $cases;
  }

  /**
   * @covers ::transform
   */
  public function testTransformInvalidInput(): void {
    $value = 'string';
    $this->expectException(MigrateSkipRowException::class);
    $this->expectExceptionMessage('The dom_migration_lookup plugin in the destinationproperty process pipeline requires a \DOMDocument object. You can use the dom plugin to convert a string to \DOMDocument.');
    (new DomMigrationLookup($this->exampleConfiguration, 'dom_migration_lookup', [], $this->migration, $this->processPluginManager))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * @covers ::transform
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform($config_overrides, $input_string, $output_string): void {
    $configuration = $config_overrides + $this->exampleConfiguration;
    $value = Html::load($input_string);
    $document = (new DomMigrationLookup($configuration, 'dom_migration_lookup', [], $this->migration, $this->processPluginManager))
      ->transform($value, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertTrue($document instanceof \DOMDocument);
    $this->assertEquals($output_string, Html::serialize($document));
  }

  /**
   * Dataprovider for testTransform().
   */
  public static function providerTestTransform(): array {
    $cases = [
      'users-migration' => [
        [],
        '<a href="/user/123">text</a>',
        '<a href="/user/321">text</a>',
      ],
      'people-migration' => [
        [],
        '<a href="https://www.example.com/user/456">text</a>',
        '<a href="https://www.example.com/people/654">text</a>',
      ],
      'no-match' => [
        ['search' => '@www\.mysite\.com/user/(\d+)@'],
        '<a href="https://www.example.com/user/456">text</a>',
        '<a href="https://www.example.com/user/456">text</a>',
      ],
    ];

    return $cases;
  }

}
