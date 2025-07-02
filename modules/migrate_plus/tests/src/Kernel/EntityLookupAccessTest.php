<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests entity lookup access check.
 *
 * @group migrate_plus
 */
final class EntityLookupAccessTest extends KernelTestBase {
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'migrate',
    'migrate_plus',
    'system',
    'user',
  ];

  /**
   * A user.
   *
   * @var bool|\Drupal\user\Entity\User
   */
  protected $user;

  /**
   * A test entity.
   */
  protected ?EntityTest $entity;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->installConfig('user');

    $this->user = $this->createUser(['view all entity_test_query_access entities']);
    $this->entity = EntityTest::create(['name' => $this->randomMachineName(8)]);
  }

  /**
   * Tests that access is honored for entity lookups.
   */
  public function testEntityLookupAccessCheck(): void {
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          ['id' => 1],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [],
      'destination' => [
        'plugin' => 'entity:entity_test',
      ],
    ];
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $executable = new MigrateExecutable($migration);
    $row = new Row();
    $configuration_base = [
      'entity_type' => 'entity_test',
      'value_key' => 'id',
    ];

    // Set access_check true.
    $configuration = $configuration_base + ['access_check' => TRUE];

    // Test as anonymous.
    $anonymous = User::getAnonymousUser();
    $this->setCurrentUser($anonymous);
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_lookup', $configuration, $migration);
    // Check the entity is not found.
    $value = $plugin->transform($this->entity->id(), $executable, $row, 'id');
    $this->assertNull($value);

    // Test as authenticated user.
    $this->setCurrentUser($this->user);
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_lookup', $configuration, $migration);
    // Check the entity is found.
    $value = $plugin->transform($this->entity->id(), $executable, $row, 'id');
    $this->assertSame($this->entity->id(), $value);

    // Retest with access check false.
    $configuration = $configuration_base + ['access_check' => FALSE];
    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_lookup', $configuration, $migration);

    // Check the entity is found.
    $value = $plugin->transform($this->entity->id(), $executable, $row, 'id');
    $this->assertSame($this->entity->id(), $value);
  }

}
