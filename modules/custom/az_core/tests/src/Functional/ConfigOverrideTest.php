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

  /**
   * Tests a config override was applied successfully after module install.
   */
  public function testConfigOverrideAfterInstall() {
    // Install the az_mail module.
    $this->container->get('module_installer')->install(['az_mail']);
    $config = $this->config('smtp.settings');
    $this->assertEquals(TRUE, $config->get('smtp_on'));
    $this->assertEquals('email-smtp.us-west-2.amazonaws.com', $config->get('smtp_host'));
    $this->assertEquals('', $config->get('smtp_hostbackup'));
    $this->assertEquals('2587', $config->get('smtp_port'));
    $this->assertEquals('tls', $config->get('smtp_protocol'));
    $this->assertEquals(TRUE, $config->get('smtp_autotls'));
    $this->assertEquals(30, $config->get('smtp_timeout'));
    $this->assertEquals('', $config->get('smtp_username'));
    $this->assertEquals('', $config->get('smtp_password'));
    $this->assertEquals('', $config->get('smtp_from'));
    $this->assertEquals('', $config->get('smtp_fromname'));
    $this->assertEquals('', $config->get('smtp_client_hostname'));
    $this->assertEquals('', $config->get('smtp_client_helo'));
    $this->assertEquals('0', $config->get('smtp_allowhtml'));
    $this->assertEquals('', $config->get('smtp_test_address'));
    $this->assertEquals('', $config->get('smtp_reroute_address'));
    $this->assertEquals(FALSE, $config->get('smtp_debugging'));
    $this->assertEquals('php_mail', $config->get('prev_mail_system'));
    $this->assertEquals(FALSE, $config->get('smtp_keepalive'));
  }

}
