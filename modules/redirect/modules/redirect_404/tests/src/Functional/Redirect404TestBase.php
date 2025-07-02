<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect_404\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing redirect 404 paths.
 */
abstract class Redirect404TestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'redirect_404',
    'node',
    'path',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Permissions for the admin user.
   *
   * @var array
   */
  protected $adminPermissions = [
    'administer redirects',
    'administer redirect settings',
    'access content',
    'bypass node access',
    'create url aliases',
    'administer url aliases',
  ];

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Create an admin user.
    $this->adminUser = $this->drupalCreateUser($this->adminPermissions);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);
  }

  /**
   * Passes if the language of the 404 path IS found on the loaded page.
   *
   * Because assertText() checks also in the Language select options, this
   * specific assertion in the redirect 404 table body is needed.
   *
   * @param string $language
   *   The language to assert in the redirect 404 table body.
   */
  protected function assertLanguageInTableBody($language) {
    $this->assertSession()->elementContains('css', 'table tbody', $language);
  }

  /**
   * Passes if the language of the 404 path is NOT found on the loaded page.
   *
   * Because assertText() checks also in the Language select options, this
   * specific assertion in the redirect 404 table body is needed.
   *
   * @param string $language
   *   The language to assert in the redirect 404 table body.
   */
  protected function assertNoLanguageInTableBody($language) {
    $this->assertSession()->elementNotContains('css', 'table tbody', $language);
  }

}
