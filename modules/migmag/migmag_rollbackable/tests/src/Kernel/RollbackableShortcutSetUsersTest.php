<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\shortcut\ShortcutSetStorageInterface;

/**
 * Tests the 'migmag_rollbackable_shortcut_set_users' destination.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableShortcutSetUsers
 *
 * @group migmag_rollbackable
 */
class RollbackableShortcutSetUsersTest extends RollbackableDestinationTestBase {

  use UserCreationTrait;

  /**
   * Base definition for the test migrations.
   *
   * @const array
   */
  const USER_SHORTCUT_SET_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'uid' => 22,
          'set_name' => 'shortcut-set-22-base',
        ],
      ],
      'ids' => [
        'uid' => ['type' => 'integer'],
        'set_name' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'uid' => 'uid',
      'set_name' => 'set_name',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_shortcut_set_users',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
    'shortcut',
  ];

  /**
   * The user whose shortcut set migration is tested.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->testUser = $this->createUser([], 'test_user', FALSE, ['uid' => 22]);

    $this->installEntitySchema('shortcut');
    $this->installSchema('shortcut', ['shortcut_set_users']);
    $this->installConfig(['shortcut']);

    ShortcutSet::create([
      'id' => 'shortcut-set-22-init',
      'label' => 'Initial shortcut set of user 22',
    ])->save();
    ShortcutSet::create([
      'id' => 'shortcut-set-22-base',
      'label' => 'Base shortcut set of user 22',
    ])->save();
    ShortcutSet::create([
      'id' => 'shortcut-set-22-subsequent',
      'label' => 'Subsequent shortcut set of user 22',
    ])->save();
  }

  /**
   * Tests the rollbackability of 'rollbackable_shortcut_set_users' destination.
   */
  public function testShortcutSetUsersRollback() {
    $this->assertEquals('default', $this->getShortcutSetIdDisplayedToUser($this->testUser));

    // Import...
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    // Check the shortcut set assigned to the test user after the base
    // migration was executed.
    drupal_static_reset();
    $this->assertEquals('shortcut-set-22-base', $this->getShortcutSetIdDisplayedToUser($this->testUser));

    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    // Check the shortcut set assigned to the test user after the subsequent
    // migration was executed.
    drupal_static_reset();
    $this->assertEquals('shortcut-set-22-subsequent', $this->getShortcutSetIdDisplayedToUser($this->testUser));

    $subsequent_executable->rollback();

    // Check the shortcut set assigned to the test user after the subsequent
    // migration was rolled back.
    drupal_static_reset();
    $this->assertEquals('shortcut-set-22-base', $this->getShortcutSetIdDisplayedToUser($this->testUser));

    // Roll back the base migration.
    $base_executable->rollback();

    drupal_static_reset();
    $this->assertEquals('default', $this->getShortcutSetIdDisplayedToUser($this->testUser));
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'user_shortcut_set_base'] + self::USER_SHORTCUT_SET_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::USER_SHORTCUT_SET_MIGRATION_BASE;
    $definition['id'] = 'user_shortcut_set_base_subsequent';
    $definition['source']['data_rows'] = [
      [
        'uid' => 22,
        'set_name' => 'shortcut-set-22-subsequent',
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * Returns the ID of the shortcut set displayed to the given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   *
   * @return string
   *   The ID of the shortcut set displayed to the given user.
   */
  protected function getShortcutSetIdDisplayedToUser(AccountInterface $account): string {
    return DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      function () use ($account) {
        $shortcutSetStorage = \Drupal::entityTypeManager()->getStorage('shortcut_set');
        $this->assertInstanceOf(ShortcutSetStorageInterface::class, $shortcutSetStorage);
        return $shortcutSetStorage->getDisplayedToUser($account)->id();
      },
      function () use ($account) {
        // @phpstan-ignore-next-line
        return shortcut_current_displayed_set($account)->id();
      }
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration(): ?MigrationInterface {
    // User shortcut set destination cannot have translation destination.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    // User shortcut set destination cannot have translation destination.
    return NULL;
  }

}
