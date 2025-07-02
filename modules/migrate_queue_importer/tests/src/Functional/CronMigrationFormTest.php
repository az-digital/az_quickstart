<?php

namespace Drupal\Tests\migrate_queue_importer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the cron migration form.
 *
 * @group migrate_queue_importer
 */
class CronMigrationFormTest extends BrowserTestBase {

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
   * Tests adding a cron migration via form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testAddCronMigrationForm() {
    $this->drupalGet('admin/config/migrate_queue_importer/cron_migration');

    $this->clickLink('Add cron migration');
    $this->assertSession()->pageTextContains('Add cron migration');

    $edit = [
      'label' => 'Nodes (migrate_queue_importer_nodes)',
      'id' => 'migrate_queue_importer_nodes_created',
      'migration' => 'Nodes (migrate_queue_importer_nodes)',
      'time' => 60,
      'update' => TRUE,
      'sync' => TRUE,
      'ignore_dependencies' => FALSE,
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $cron_migration */
    $cron_migration = $this->storage->load('migrate_queue_importer_nodes_created');

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($cron_migration);
    $this->assertEquals('migrate_queue_importer_nodes_created', $cron_migration->id());
    $this->assertEquals('Nodes (migrate_queue_importer_nodes)', $cron_migration->label());
    $this->assertEquals('migrate_queue_importer_nodes', $cron_migration->migration);
    $this->assertEquals('60', $cron_migration->time);
    $this->assertEquals(TRUE, $cron_migration->update);
    $this->assertEquals(TRUE, $cron_migration->sync);
    $this->assertEquals(FALSE, $cron_migration->ignore_dependencies);
    $this->assertEquals(TRUE, $cron_migration->status());
  }

  /**
   * Tests editing a cron migration via form.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testEditCronMigrationForm() {
    $this->testAddCronMigrationForm();

    $this->drupalGet('admin/config/migrate_queue_importer/cron_migration');
    $this->clickLink('Edit');
    $this->assertSession()->pageTextContains('Edit cron migration');

    $edit = [
      'label' => 'Nodes (migrate_queue_importer_nodes)',
      'migration' => 'Nodes (migrate_queue_importer_nodes)',
      'time' => 120,
      'update' => FALSE,
      'sync' => FALSE,
      'ignore_dependencies' => TRUE,
    ];
    $this->submitForm($edit, 'Save');

    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $cron_migration */
    $cron_migration = $this->storage->load('migrate_queue_importer_nodes_created');

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($cron_migration);
    $this->assertEquals('migrate_queue_importer_nodes_created', $cron_migration->id());
    $this->assertEquals('Nodes (migrate_queue_importer_nodes)', $cron_migration->label());
    $this->assertEquals('migrate_queue_importer_nodes', $cron_migration->migration);
    $this->assertEquals(120, $cron_migration->time);
    $this->assertEquals(FALSE, $cron_migration->update);
    $this->assertEquals(FALSE, $cron_migration->sync);
    $this->assertEquals(TRUE, $cron_migration->ignore_dependencies);
    $this->assertEquals(TRUE, $cron_migration->status());
  }

}
