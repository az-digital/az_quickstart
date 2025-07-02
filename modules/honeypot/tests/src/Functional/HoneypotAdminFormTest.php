<?php

declare(strict_types=1);

namespace Drupal\Tests\honeypot\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test Honeypot spam protection admin form functionality.
 *
 * @group honeypot
 */
class HoneypotAdminFormTest extends BrowserTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['honeypot'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up admin user.
    $this->adminUser = $this->drupalCreateUser([
      'administer honeypot',
      'bypass honeypot protection',
    ]);
  }

  /**
   * Test a valid element name.
   */
  public function testElementNameUpdateSuccess(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "test";
    $this->drupalGet('admin/config/content/honeypot');
    $this->submitForm($edit, 'Save configuration');

    // Form should have been submitted successfully.
    $assert->pageTextContains('The configuration options have been saved.');

    // Set up form and submit it.
    $edit['element_name'] = "test-1";
    $this->drupalGet('admin/config/content/honeypot');
    $this->submitForm($edit, 'Save configuration');

    // Form should have been submitted successfully.
    $assert->pageTextContains('The configuration options have been saved.');
  }

  /**
   * Test an invalid element name (invalid first character).
   */
  public function testElementNameUpdateFirstCharacterFail(): void {
    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "1test";
    $this->drupalGet('admin/config/content/honeypot');
    $this->submitForm($edit, 'Save configuration');

    // Form submission should fail.
    $this->assertSession()->pageTextContains('The element name must start with a letter.');
  }

  /**
   * Test an invalid element name (invalid character in name).
   */
  public function testElementNameUpdateInvalidCharacterFail(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in the admin user.
    $this->drupalLogin($this->adminUser);

    // Set up form and submit it.
    $edit['element_name'] = "special-character-&";
    $this->drupalGet('admin/config/content/honeypot');
    $this->submitForm($edit, 'Save configuration');

    // Form submission should fail.
    $assert->pageTextContains('The element name cannot contain spaces or other special characters.');

    // Set up form and submit it.
    $edit['element_name'] = "space in name";
    $this->drupalGet('admin/config/content/honeypot');
    $this->submitForm($edit, 'Save configuration');

    // Form submission should fail.
    $assert->pageTextContains('The element name cannot contain spaces or other special characters.');
  }

}
