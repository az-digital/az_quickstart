<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_404\Functional;

use Drupal\Core\Database\Database;

/**
 * Tests suppressing 404 logs if the suppress_404 setting is enabled.
 *
 * @group redirect_404
 */
class Redirect404LogSuppressorTest extends Redirect404TestBase {

  /**
   * Additional modules to enable.
   *
   * @var array
   */
  protected static $modules = ['dblog'];

  /**
   * A user with some relevant administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * A user without any permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create users with specific permissions.
    $this->adminUser = $this->drupalCreateUser([
      'administer redirect settings',
      'administer redirects',
    ]);
    $this->webUser = $this->drupalCreateUser([]);
  }

  /**
   * Tests the suppress_404 service.
   */
  public function testSuppress404Events() {
    // Cause a page not found and an access denied event.
    $this->drupalGet('page-not-found');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(403);

    // Assert the events are logged in the dblog reports.
    $this->assertEquals(1, Database::getConnection()->query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'page not found'")->fetchField());
    $this->assertEquals(1, Database::getConnection()->query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'access denied'")->fetchField());

    // Login as admin and enable suppress_404 to avoid logging the 404 event.
    $this->drupalLogin($this->adminUser);
    $edit = ['suppress_404' => TRUE];
    $this->drupalGet('admin/config/search/redirect/settings');
    $this->submitForm($edit, 'Save configuration');

    // Cause again a page not found and an access denied event.
    $this->drupalGet('page-not-found');
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalLogin($this->webUser);
    $this->drupalGet('admin/reports/dblog');
    $this->assertSession()->statusCodeEquals(403);

    // Assert only the new access denied event is logged now.
    $this->drupalLogin($this->adminUser);
    $this->assertEquals(1, Database::getConnection()->query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'page not found'")->fetchField());
    $this->assertEquals(2, Database::getConnection()->query("SELECT COUNT(*) FROM {watchdog} WHERE type = 'access denied'")->fetchField());

  }

}
