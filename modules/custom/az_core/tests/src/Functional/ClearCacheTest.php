<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Core\Url;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test to ensure the Quickstart settings clear cache button works correctly.
 */
#[Group('az_core')]
class ClearCacheTest extends QuickstartFunctionalTestBase {

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
  protected $defaultTheme = 'claro';

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
