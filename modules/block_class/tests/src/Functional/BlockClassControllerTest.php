<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Block Class administration pages.
 *
 * @group block_class
 */
class BlockClassControllerTest extends BrowserTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_class'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up admin user.
    $this->adminUser = $this->drupalCreateUser([
      BlockClassConstants::BLOCK_CLASS_PERMISSION,
      'administer blocks',
      // Required for the 'Help' link.
      'access administration pages',
      // Required for the permissions configuration link.
      'administer permissions',
    ]);
  }

  /**
   * Test backend admin configuration and listing pages.
   */
  public function testBlockClassAdminPages(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in as an admin user to test admin pages.
    $this->drupalLogin($this->adminUser);

    // Test the 'Help' page does not crash with error and displays properly.
    $this->drupalGet('admin/block-class/help');
    // Check page title.
    $assert->responseContains('<h1 class="page-title">Block Class Help</h1>');
    // Check 'block_class_help' is properly invoked and displays its contents.
    $assert->responseContains("<p>Block Class allows users to add classes to any block through the block's configuration interface. Hooray for more powerful block theming!</p>");

    // Check module admin tasks links.
    $assert->responseContains('<h3>Block Class administration pages</h3>');
    // Check the 'Help' link (to the current page) is displayed.
    $assert->elementExists('xpath', '//ul[@class="links"]//a[contains(@href, "/admin/block-class/help") and (text()=\'Help\')]');
    // Check the permissions link is displaying properly (#3412155).
    $assert->elementExists('xpath', '//ul[@class="links"]//a[contains(@href, "/admin/people/permissions/module/block_class") and (text()=\'Configure Block Class permissions\')]');
  }

}
