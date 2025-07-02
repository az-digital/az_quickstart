<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Block Class administration pages.
 *
 * @group block_class
 */
class BlockClassSettingsFormTest extends BrowserTestBase {

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
      'access administration pages',
    ]);
  }

  /**
   * Test backend admin configuration and listing pages.
   */
  public function testBlockClassFormAddOperation(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in as an admin user to test admin pages.
    $this->drupalLogin($this->adminUser);

    // Test the 'Block List' page has no item to display.
    $this->drupalGet('admin/config/content/block-class/settings');
    $this->submitForm([], 'Submit');
    $assert->pageTextContains('The configuration options have been saved.');
  }

}
