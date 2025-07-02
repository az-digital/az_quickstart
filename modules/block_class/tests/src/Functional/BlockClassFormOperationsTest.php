<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Block Class administration pages.
 *
 * @group block_class
 */
class BlockClassFormOperationsTest extends BrowserTestBase {

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
    $this->drupalGet('admin/config/content/block-class/list');
    // Check the table doesn't have any element ('tr', 'td').
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody');
    $assert->elementNotExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');

    // Add a breadcrumbs block with custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_breadcrumb_block/claro', ['query' => ['region' => 'highlighted']]);
    $edit = [
      'region' => 'highlighted',
      'class[third_party_settings][block_class][classes_0]' => 'testBlockClass0',
      'class[third_party_settings][block_class][classes_1]' => 'testBlockClass1',
      'class[third_party_settings][block_class][classes_2]' => 'testBlockClass2',
    ];
    $this->submitForm($edit, 'Save block');
    $assert->pageTextContains('The block configuration has been saved.');

    // Test the 'Block List' page has items to display.
    $this->drupalGet('admin/config/content/block-class/list');
    $assert->elementExists('xpath', '//div[contains(@class, "region-content")]//div[contains(@id, "content")]//table//tbody//tr');
    $assert->responseContains('<td>testBlockClass0 testBlockClass1 testBlockClass2</td>');

    // Check breadcrumbs block links on page.
    $assert->elementExists('xpath', '//div[contains(@class, "region-content")]//div[contains(@id, "content")]//table//tbody//tr//td//a[contains(@href, "admin/structure/block/manage") and contains(@href, "breadcrumbs") and (text()="Edit")]');
    $assert->elementExists('xpath', '//div[contains(@class, "region-content")]//div[contains(@id, "content")]//table//tbody//tr//td//a[contains(@href, "admin/config/content/block-class/delete") and contains(@href, "breadcrumbs") and (text()="Delete")]');
    $assert->elementExists('xpath', '//div[contains(@class, "region-content")]//div[contains(@id, "content")]//table//tbody//tr//td//a[contains(@href, "admin/config/content/block-class/delete-attribute") and contains(@href, "breadcrumbs") and (text()="Delete Attributes")]');

  }

}
