<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Block Class Bulk Operations pages.
 *
 * @group block_class
 */
class BlockClassBulkOperationsTest extends BrowserTestBase {

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
  public function testBlockClassBulkOperationsForms(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $test_classes = 'testBlockClass1 testBlockClass2 testBlockClass3';

    // Log in as an admin user to test admin pages.
    $this->drupalLogin($this->adminUser);

    // Test the 'Block List' page has no item to display.
    $this->drupalGet('admin/config/content/block-class/list');
    // Check the table doesn't have any element ('tr', 'td').
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody');
    $assert->elementNotExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');

    // Test the 'Bulk Operations' forms: insert classes.
    $this->drupalGet('admin/config/content/block-class/bulk-operations');

    $edit = [
      'operation' => 'insert',
      'classes_to_be_added' => $test_classes,
    ];
    $this->submitForm($edit, 'Run');

    $assert->pageTextContains('Do you want to insert class(es)');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('Bulk operation concluded');

    // Test the 'Block List' page has items to display.
    $this->drupalGet('admin/config/content/block-class/list');
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');
    $assert->responseContains('<td>' . $test_classes . '</td>');
    // Try to find the block classes on the 'breadcrumbs' block.
    $assert->elementExists('xpath', '//div[contains(@class, "region-breadcrumb")]//div[contains(@id, "breadcrumbs") and contains(@class, "' . $test_classes . '")]');

    // Test single delete class confirm form.
    $this->drupalGet('admin/config/content/block-class/delete/' . $this->defaultTheme . '_breadcrumbs');
    $assert->pageTextContains('Are you sure?');
    $this->submitForm([], 'Confirm');
    // Try to find the block classes on the 'breadcrumbs' block.
    $assert->elementNotExists('xpath', '//div[contains(@class, "region-breadcrumb")]//div[contains(@id, "breadcrumbs") and contains(@class, "' . $test_classes . '")]');
    $assert->pageTextContains('Block Class deleted');

    // Test the 'Bulk Operations' forms: delete classes.
    $this->drupalGet('admin/config/content/block-class/bulk-operations');

    $edit = [
      'operation' => 'delete',
    ];
    $this->submitForm($edit, 'Run');

    $assert->pageTextContains('Do you want to run this bulk operation for all block classes?');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('Bulk operation concluded');

    // Test the 'Block List' page has no item to display.
    $this->drupalGet('admin/config/content/block-class/list');
    // Check the table doesn't have any element ('tr', 'td').
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody');
    $assert->elementNotExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');

    // Test the 'Bulk Operations' forms: insert attributes.
    $this->drupalGet('admin/config/content/block-class/bulk-operations');

    $edit = [
      'operation' => 'insert_attributes',
      // 'attributes_to_be_added' => "data-block-type1|info1\r\ndata-block-type2|info2\r\ndata-block-type3|info3",
      'attributes_to_be_added' => "data-block-type1|info1\ndata-block-type2|info2\ndata-block-type3|info3",
    ];
    $this->submitForm($edit, 'Run');

    $assert->pageTextContains('Do you want to insert attribute(s)');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('Bulk operation concluded');

    // Test the 'Block List' page has items to display.
    $this->drupalGet('admin/config/content/block-class/list');
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');
    $assert->responseContains('<td>data-block-type1|info1<br>data-block-type2|info2<br>data-block-type3|info3</td>');
    $assert->elementExists('xpath', '//div[contains(@class, "region-breadcrumb")]//div[contains(@id, "breadcrumbs") and @data-block-type1="info1" and @data-block-type2="info2" and @data-block-type3="info3"]');

    // Test single delete attribute confirm form.
    $this->drupalGet('admin/config/content/block-class/delete-attribute/' . $this->defaultTheme . '_breadcrumbs');

    $assert->pageTextContains('Are you sure?');
    $this->submitForm([], 'Confirm');
    // Try to find the block classes on the 'breadcrumbs' block.
    $assert->elementNotExists('xpath', '//div[contains(@class, "region-breadcrumb")]//div[contains(@id, "breadcrumbs") and contains(@class, "' . $test_classes . '")]');
    $assert->pageTextContains('Attributes deleted');
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody//tr');
    $assert->responseContains('<td>data-block-type1|info1<br>data-block-type2|info2<br>data-block-type3|info3</td>');
    $assert->elementNotExists('xpath', '//div[contains(@class, "region-breadcrumb")]//div[contains(@id, "breadcrumbs") and @data-block-type1="info1" and @data-block-type2="info2" and @data-block-type3="info3"]');

    // Test the 'Bulk Operations' forms: delete classes.
    $this->drupalGet('admin/config/content/block-class/bulk-operations');

    $edit = [
      'operation' => 'delete_attributes',
    ];
    $this->submitForm($edit, 'Run');

    $assert->pageTextContains('Do you want to delete all attributes?');
    $this->submitForm([], 'Confirm');
    $assert->pageTextContains('Bulk operation concluded');

    // Test the 'Block List' page has no item to display.
    $this->drupalGet('admin/config/content/block-class/list');
    // Check the table doesn't have any element ('tr', 'td').
    $assert->elementExists('xpath', '//div[@id="block-claro-content"]//table//tbody');

  }

}
