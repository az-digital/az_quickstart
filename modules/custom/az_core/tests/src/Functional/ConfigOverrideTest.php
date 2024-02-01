<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test to ensure Quickstart configuration overrides work correctly.
 *
 * @ingroup az_core
 *
 * @group az_core
 */
class ConfigOverrideTest extends BrowserTestBase {

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
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'az_cas',
    'az_core',
    'cas',
  ];

  /**
   * Tests a config override was applied successfully.
   */
  public function testConfigOverride() {

    // Check that the cas server hostname has been overridden.
    $hostname = $this->config('cas.settings')->get('server.hostname');
    $this->assertEquals('shibboleth.arizona.edu', $hostname);
  }

}
