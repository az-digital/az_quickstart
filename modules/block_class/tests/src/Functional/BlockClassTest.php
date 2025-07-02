<?php

namespace Drupal\Tests\block_class\Functional;

use Drupal\block_class\Constants\BlockClassConstants;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore mainpagecontent, useraccountmenu
// Ignore CSS class names used in the tests.
/**
 * Tests the custom CSS classes for blocks.
 *
 * @group block_class
 */
class BlockClassTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'block_class'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'olivero';

  /**
   * Tests the custom CSS classes for blocks.
   */
  public function testBlockClass() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $admin_user = $this->drupalCreateUser([
      BlockClassConstants::BLOCK_CLASS_PERMISSION,
      'administer blocks',
    ]);
    $this->drupalLogin($admin_user);

    // Add a content block with custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_main_block/olivero', ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'content',
      'class[third_party_settings][block_class][classes_0]' => 'TestClass_content',
    ];
    $this->submitForm($edit, 'Save block');

    // Add a user account menu with a custom CSS class.
    $this->drupalGet('admin/structure/block/add/system_menu_block:account/olivero', ['query' => ['region' => 'content']]);
    $edit = [
      'region' => 'secondary_menu',
      'class[third_party_settings][block_class][classes_0]' => 'TestClass_menu',
    ];
    $this->submitForm($edit, 'Save block');

    // Go to the front page of the user.
    $this->drupalGet('<front>');
    // Assert the custom class in the content block.
    if (version_compare(\Drupal::VERSION, '10', '<')) {
      // Support tests for D9.
      $assert->responseContains('<div id="block-mainpagecontent" class="TestClass_content block block-system block-system-main-block">');
      // Assert the custom class in user menu.
      $assert->responseContains('<nav  id="block-useraccountmenu" class="TestClass_menu block block-menu navigation menu--account secondary-nav" aria-labelledby="block-useraccountmenu-menu" role="navigation">');
    }
    else {
      $assert->responseContains('<div id="block-olivero-mainpagecontent" class="TestClass_content block block-system block-system-main-block">');
      // Assert the custom class in user menu.
      $assert->responseContains('<nav  id="block-olivero-useraccountmenu" class="TestClass_menu block block-menu navigation menu--account secondary-nav" aria-labelledby="block-olivero-useraccountmenu-menu" role="navigation">');
    }
  }

}
