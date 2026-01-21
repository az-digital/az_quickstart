<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Core\Url;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test to ensure that monitoring page works correctly.
 */
#[Group('az_core')]
class MonitoringPageTest extends QuickstartFunctionalTestBase {

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
  protected $defaultTheme = 'stark';

  /**
   * Tests the monitoring page functionality.
   */
  public function testMonitoringPage() {
    $user = $this->drupalCreateUser([
      'administer site configuration',
      'administer quickstart configuration',
    ]);
    $this->drupalLogin($user);
    $assert = $this->assertSession();

    // Enable anonymous page caching.
    $this->drupalGet(Url::fromRoute('system.performance_settings'));
    $assert->statusCodeEquals(200);
    $edit = [
      'page_cache_maximum_age' => 3600,
    ];
    $this->submitForm($edit, 'Save configuration', 'system-performance-settings');

    // Configure monitoring page settings.
    $this->drupalGet(Url::fromRoute('az_core.az_settings'));
    $assert->statusCodeEquals(200);
    $edit = [
      'monitoring_page_enabled' => TRUE,
      'monitoring_page_path' => 'monitoring-page',
    ];
    $this->submitForm($edit, 'Save configuration', 'az-core-settings');

    // Clear caches.
    drupal_flush_all_caches();

    // Log out.
    $this->drupalLogout();

    // Verify front page is cache-able.
    $this->drupalGet(Url::fromRoute('<front>'));
    $assert->responseHeaderEquals('Cache-Control', 'max-age=3600, public');

    // Verify monitoring page is not cache-able or index-able.
    $this->drupalGet(Url::fromRoute('az_core.monitoring_page'));
    $assert->responseHeaderEquals('Cache-Control', 'must-revalidate, no-cache, private');
    $assert->responseHeaderEquals('X-Robots-Tag', 'none');
  }

}
