<?php

namespace Drupal\Tests\migrate_queue_importer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests various tasks done via the UI.
 *
 * @group migrate_queue_importer
 */
class CronMigrationStatusTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'filter',
    'field',
    'node',
    'text',
    'taxonomy',
    'block',
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'migrate_queue_importer',
    'migrate_queue_importer_test',
  ];

  /**
   * The cron_migration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We're testing local actions.
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $account = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'administer cron migrations',
    ]);

    $this->drupalLogin($account);

    $this->storage = $this->container->get('entity_type.manager')->getStorage('cron_migration');
  }

  /**
   * Test deactivate cron migration.
   */
  public function testDeactivateCronMigration() {
    $this->drupalGet('admin/config/migrate_queue_importer/cron_migration');
    $this->clickLink('Disable');

    $cron_migration = $this->storage->load('nodes_migrate_queue_importer_nodes');

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($cron_migration);
    $this->assertEquals(FALSE, $cron_migration->status());
  }

  /**
   * Test activate cron migration.
   */
  public function testActivateCronMigration() {
    $this->testDeactivateCronMigration();

    $this->drupalGet('admin/config/migrate_queue_importer/cron_migration');
    $this->clickLink('Enable');

    $cron_migration = $this->storage->load('nodes_migrate_queue_importer_nodes');

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($cron_migration);
    $this->assertEquals(TRUE, $cron_migration->status());
  }

}
