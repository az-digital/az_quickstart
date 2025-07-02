<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the default_entity_value plugin.
 *
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\DefaultEntityValue
 * @group migrate_plus
 */
final class DefaultEntityValueTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_plus',
    'migrate',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
  }

  /**
   * Tests the lookup when the value is empty.
   *
   * @covers ::transform
   */
  public function testDefaultEntityValue(): void {
    // Create a user.
    $editorial_user = $this->createUser([], 'editorial');
    $journalist_user = $this->createUser([], 'journalist');
    // Setup test migration objects.
    $migration_prophecy = $this->prophesize(MigrationInterface::class);
    $migrate_destination_prophecy = $this->prophesize(MigrateDestinationInterface::class);
    $migrate_destination_prophecy->getPluginId()->willReturn('user');
    $migrate_destination = $migrate_destination_prophecy->reveal();
    $migration_prophecy->getDestinationPlugin()->willReturn($migrate_destination);
    $migration_prophecy->getProcess()->willReturn([]);
    $migration = $migration_prophecy->reveal();
    $configuration = [
      'entity_type' => 'user',
      'value_key' => 'name',
      'ignore_case' => TRUE,
      'default_value' => 'editorial',
    ];
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('default_entity_value', $configuration, $migration);
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row();
    // Check the case default value is not used.
    $value = $plugin->transform($journalist_user->id(), $executable, $row, 'name');
    $this->assertSame($journalist_user->id(), $value);
    // Check the default value is found.
    $value = $plugin->transform('', $executable, $row, 'name');
    $this->assertSame($editorial_user->id(), $value);
  }

}
