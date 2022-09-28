<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Datetime\Entity\DateFormat;

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
    'az_core_test',
    'dblog'
  ];

  /**
   * Tests a config override was applied successfully.
   */
  public function testConfigOverride() {

    // Check that the cas server hostname has been overridden.
    $hostname = $this->config('cas.settings')->get('server.hostname');
    $this->assertEquals('shibboleth.arizona.edu', $hostname);
  }

  /**
   * Tests a config override doesn't prevent install config from importing.
   */
  public function testConfigOverrideWithInstallConfig() {

    // Check that az_core_test module override works.
    $dblog_limit = $this->config('dblog.settings')->get('row_limit');
    $this->assertEquals(950, $dblog_limit);

    // Check that override config doesn't prevent install config from importing.
    $az_test_format = DateFormat::load('az_test');
    $this->assertEquals('Y - H:i', $az_test_format->getPattern());
  }

}
