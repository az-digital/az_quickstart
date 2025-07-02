<?php

namespace Drupal\Tests\upgrade_status\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the accessibility of the deprecation dashboard.
 *
 * @group upgrade_status
 */
class UpgradeStatusAccessTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['upgrade_status'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access without permission.
   */
  #[\ReturnTypeWillChange]
  public function testDeprecationDashboardAccessUnprivileged() {
    $this->drupalGet(Url::fromRoute('upgrade_status.report'));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests access with user that has the correct permission.
   */
  public function testDeprecationDashboardAccessPrivileged() {
    $this->drupalLogin($this->drupalCreateUser(['administer software updates']));
    $this->drupalGet(Url::fromRoute('upgrade_status.report'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
