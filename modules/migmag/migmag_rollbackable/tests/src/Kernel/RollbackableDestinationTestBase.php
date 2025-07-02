<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\migmag_rollbackable\RollbackableInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for testing rollbackable destination plugins.
 */
abstract class RollbackableDestinationTestBase extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migmag_rollbackable',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $this->installSchema(
      'migmag_rollbackable',
      [
        RollbackableInterface::ROLLBACK_DATA_TABLE,
        RollbackableInterface::ROLLBACK_STATE_TABLE,
      ]
    );

    EntityViewMode::create([
      'targetEntityType' => 'user',
      'id' => "user.full",
    ])->save();
  }

  /**
   * Returns the base migration for the actual test.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The base migration for the actual test.
   */
  abstract protected function baseMigration(): MigrationInterface;

  /**
   * Returns a subsequent migration for the actual test.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   Subsequent migration for the actual test.
   */
  abstract protected function subsequentMigration(): MigrationInterface;

  /**
   * Returns the base translation migration for the actual test.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface|null
   *   The base migration for the actual test.
   */
  abstract protected function baseTranslationMigration(): ?MigrationInterface;

  /**
   * Returns a subsequent translation migration for the actual test.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface|null
   *   Subsequent migration for the actual test.
   */
  abstract protected function subsequentTranslationMigration(): ?MigrationInterface;

  /**
   * Test data provider.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestMigrationRollback() :array {
    return [
      'No preexisting object' => [
        'with_preexisting_config' => FALSE,
      ],
      'With preexisting object' => [
        'with_preexisting_config' => TRUE,
      ],
    ];
  }

  /**
   * Instantiates a migration plugin instance from the given plugin definition.
   *
   * @param array $migration_plugin_definition
   *   The migration plugin definition.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   A migration plugin instance created form the given definition.
   */
  protected function getMigrationPluginInstance(array $migration_plugin_definition) {
    $manager = $this->container->get('plugin.manager.migration', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    assert($manager instanceof MigrationPluginManagerInterface);
    $migration = $manager->createStubMigration($migration_plugin_definition);
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    return $migration;
  }

  /**
   * Creates a migrate executable from the given migration plugin instance.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration plugin instance.
   *
   * @return \Drupal\migrate\MigrateExecutable
   *   A migration plugin instance created form the given definition.
   */
  protected function getMigrateExecutable(MigrationInterface $migration) {
    return new MigrateExecutable($migration, $this);
  }

  /**
   * Asserts whether no migration errors were logged to $this->migrateMessages.
   *
   * This is a DX helper method which makes migration messages easy to capture
   * when something goes wrong.
   */
  protected function assertNoErrors() {
    foreach ($this->migrateMessages as $severity => $messages) {
      $actual[$severity] = array_reduce(
        $messages,
        function (array $carry, $message) {
          $carry[] = (string) $message;
          return $carry;
        },
        []
      );
      $dummy[$severity] = array_fill(0, count($messages), '');
    }
    $this->assertEquals($dummy ?? [], $actual ?? []);
  }

}
