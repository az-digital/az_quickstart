<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Execution form test.
 *
 * @group migrate_tools
 */
final class MigrateListBuilderTest extends BrowserTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'migrate_tools_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Log in as user 1. Migrations in the UI can only be performed as user 1.
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Test migrate UI list page with default migrations.
   */
  public function testMigrateListBuilderDefault(): void {
    // List migrations from default group.
    $this->drupalGet('/admin/structure/migrate/manage/default/migrations');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test migrate UI list page with disabled migrations.
   */
  public function testMigrateListBuilderDisabled(): void {
    // List migrations containing disabled migrations.
    $this->drupalGet('/admin/structure/migrate/manage/disabled/migrations');
    $this->assertSession()->statusCodeEquals(200);
  }

}
