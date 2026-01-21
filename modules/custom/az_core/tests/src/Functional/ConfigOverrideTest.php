<?php

namespace Drupal\Tests\az_core\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test to ensure Quickstart configuration overrides work correctly.
 */
#[Group('az_core')]
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
   * @var string[]
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
    $this->assertSame('shibboleth.arizona.edu', $hostname);
  }

  /**
   * Tests a config override was applied successfully after module install.
   */
  public function testConfigOverrideAfterInstall() {
    // Install the az_mail module.
    $this->container->get('module_installer')->install(['az_mail']);
    $config = $this->config('smtp.settings');
    $this->assertTrue($config->get('smtp_on'));
    $this->assertSame('email-smtp.us-west-2.amazonaws.com', $config->get('smtp_host'));
    $this->assertSame('', $config->get('smtp_hostbackup'));
    $this->assertSame('2587', $config->get('smtp_port'));
    $this->assertSame('tls', $config->get('smtp_protocol'));
    $this->assertTrue($config->get('smtp_autotls'));
    $this->assertSame(30, $config->get('smtp_timeout'));
    $this->assertSame('', $config->get('smtp_username'));
    $this->assertSame('', $config->get('smtp_password'));
    $this->assertSame('', $config->get('smtp_from'));
    $this->assertSame('', $config->get('smtp_fromname'));
    $this->assertSame('', $config->get('smtp_client_hostname'));
    $this->assertSame('', $config->get('smtp_client_helo'));
    $this->assertFalse($config->get('smtp_allowhtml'));
    $this->assertSame('', $config->get('smtp_test_address'));
    $this->assertSame('', $config->get('smtp_reroute_address'));
    $this->assertFalse($config->get('smtp_debugging'));
    $this->assertSame('php_mail', $config->get('prev_mail_system'));
    $this->assertFalse($config->get('smtp_keepalive'));
    $this->assertSame(1, $config->get('smtp_debug_level'));
  }

}
