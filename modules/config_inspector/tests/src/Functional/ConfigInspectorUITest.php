<?php

namespace Drupal\Tests\config_inspector\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * User interface tests for configuration inspector.
 *
 * @group config_inspector
 */
class ConfigInspectorUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'config_inspector'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->drupalPlaceBlock('local_tasks_block');

    $permissions = [
      'inspect configuration',
    ];
    // Create and login user.
    $admin_user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests the listing page for inspecting configuration.
   */
  public function testConfigInspectorListUi() {
    $this->drupalGet('admin/reports/config-inspector');
    $this->assertSession()->responseContains('user.role.anonymous');
    foreach (['list', 'tree', 'form', 'raw'] as $type) {
      $this->assertSession()->linkByHrefExists('admin/reports/config-inspector/user.role.anonymous/' . $type);
    }

    foreach (['list', 'tree', 'form', 'raw'] as $type) {
      $this->drupalGet('admin/reports/config-inspector/user.role.anonymous/' . $type);
      $this->assertSession()->pageTextContains('Label');
      // Assert this as raw text, so we can find even as form default value.
      $this->assertSession()->responseContains('Anonymous user');

      // Make sure the tabs are present.
      $this->assertSession()->linkExists('List');
      $this->assertSession()->linkExists('Tree');
      $this->assertSession()->linkExists('Form');
      $this->assertSession()->linkExists('Raw data');
    }
  }

}
