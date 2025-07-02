<?php

namespace Drupal\Tests\migrate_queue_importer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the cron migration list.
 *
 * @group migrate_queue_importer
 */
class CronMigrationListTest extends BrowserTestBase {

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
  }

  /**
   * Tests the cron migration list builder.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCronMigrationList() {
    $this->drupalGet('admin/config/migrate_queue_importer/cron_migration');
    $this->assertSession()->pageTextContains('Cron migration configuration');
    $this->assertSession()->statusCodeEquals(200);

    $migration_table_row = $this->xpath("//main//table/tbody/tr/td");
    $this->assertEquals(8, count($migration_table_row));
    $this->assertEquals('Nodes (migrate_queue_importer_nodes)', $migration_table_row[0]->getText());
    $this->assertEquals('✔', $migration_table_row[1]->getText());
    $this->assertEquals('1 min', $migration_table_row[2]->getText());
    $this->assertEquals('✔', $migration_table_row[3]->getText());
    $this->assertEquals('✖', $migration_table_row[4]->getText());
    $this->assertEquals('✖', $migration_table_row[5]->getText());
    $this->assertEquals('nodes_migrate_queue_importer_nodes', $migration_table_row[6]->getText());
  }

}
