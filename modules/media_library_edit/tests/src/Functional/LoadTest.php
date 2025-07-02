<?php

namespace Drupal\Tests\media_library_edit\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group media_library_edit
 */
class LoadTest extends BrowserTestBase {

  /**
   * Set theme to be used for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['media_library_edit'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
