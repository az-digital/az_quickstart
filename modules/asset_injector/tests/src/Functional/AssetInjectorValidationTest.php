<?php

namespace Drupal\Tests\asset_injector\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests related to form validation.
 *
 * @package Drupal\Tests\asset_injector\Functional
 *
 * @group asset_injector
 */
class AssetInjectorValidationTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['asset_injector', 'toolbar', 'block'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalPlaceBlock('system_messages_block');

    $this->user = $this->drupalCreateUser([]);
    $this->drupalLogin($this->rootUser);

  }

  /**
   * Tests if the code field tag validation works correctly.
   */
  public function testCodeTagValidation() {
    $page = $this->getSession()->getPage();
    $session = $this->assertSession();
    // Go to the "Add JS injector" page.
    $this->drupalGet('/admin/config/development/asset-injector/js/add');
    $page->fillField('label', 'test_wrong_code');
    // Test different combinations of tags and check that it behaves correctly.
    $page->fillField('code', '<script>Test</script>');
    $page->pressButton('save_continue');
    $session->pageTextContains('There must be no leading or trailing <script> tags.');
    $page->fillField('code', '<script>Test');
    $page->pressButton('save_continue');
    $session->pageTextContains('There must be no leading or trailing <script> tags.');
    $page->fillField('code', 'Test</script>');
    $page->pressButton('save_continue');
    $session->pageTextContains('There must be no leading or trailing <script> tags.');
    $page->fillField('code', 'Test');
    $page->pressButton('save_continue');
    $session->pageTextNotContains('There must be no leading or trailing <script> tags.');
    // Test it with another tag name that should be rejected.
    $page->fillField('code', 'Test</style>');
    $page->pressButton('save_continue');
    $session->pageTextContains('There must be no leading or trailing <style> tags.');
    // Go to the "Add CSS injector" page.
    $this->drupalGet('/admin/config/development/asset-injector/css/add');
    $page->fillField('label', 'test_wrong_code');
    // Test if the behavior in general also applies here.
    $page->fillField('code', '<script>Test</script>');
    $page->pressButton('save_continue');
    $session->pageTextContains('There must be no leading or trailing <script> tags.');
    $page->fillField('code', 'Test');
    $page->pressButton('save_continue');
    $session->pageTextNotContains('There must be no leading or trailing <script> tags.');
  }

}
