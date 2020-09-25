<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure the Quickstart settings clear cache button works correctly.
 *
 * @group az_core
 */
class ClearCacheTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * @var string
   */
  protected $defaultTheme = 'seven';

  /**
   * Tests the clear cache button on the Quickstart settings page.
   */
  public function testClearCache() {
    $user = $this->drupalCreateUser(['administer quickstart configuration']);
    $this->drupalLogin($user);
    $assert = $this->assertSession();
    $this->drupalGet(Url::fromRoute('az_core.az_settings'));
    $this->submitForm([], 'Clear all caches', 'az-core-settings');
    $assert->pageTextContains('Caches cleared.');
  }

}
