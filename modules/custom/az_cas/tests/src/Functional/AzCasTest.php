<?php

namespace Drupal\Tests\az_cas\Functional;

use Drupal\Core\Url;
use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the AZ CAS module.
 */
#[Group('az_cas')]
class AzCasTest extends QuickstartFunctionalTestBase {

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
   * The created user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([
      'administer site configuration',
      'access administration pages',
      'administer account settings',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the config form.
   */
  public function testConfigForm() {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Access config page.
    $this->drupalGet(Url::fromRoute('cas.settings'));
    $this->assertSession()->statusCodeEquals(200);
    // Test the form elements exist and have defaults.
    $config = $this->config('cas.settings');
    $this->assertSession()->fieldValueEquals(
      'server[version]',
      // This is the az_cas overridden value.
      $config->get('server.version')
    );
    $this->assertSession()->fieldValueEquals(
      'server[protocol]',
      // This is the az_cas overridden value.
      $config->get('server.protocol')
    );
    $this->assertSession()->fieldValueEquals(
      'server[hostname]',
      // This is the az_cas overridden value.
      $config->get('server.hostname')
    );
    $this->assertSession()->fieldValueEquals(
      'server[port]',
      // This is the az_cas overridden value.
      $config->get('server.port')
    );
    $this->assertSession()->fieldValueEquals(
      'server[verify]',
      // This is the az_cas overridden value.
      $config->get('server.verify')
    );
    $this->assertSession()->fieldValueEquals(
      'server[cert]',
      // This is the az_cas overridden value.
      $config->get('server.cert')
    );
    $this->assertSession()->fieldValueEquals(
      'gateway[enabled]',
      // This is the az_cas overridden value.
      $config->get('gateway.enabled')
    );
    $this->assertSession()->fieldValueEquals(
      'gateway[recheck_time]',
      // This is the az_cas overridden value.
      $config->get('gateway.recheck_time')
    );
    $this->assertSession()->fieldValueEquals(
      'gateway[method]',
      // This is the az_cas overridden value.
      $config->get('gateway.method')
    );
    $this->assertSession()->fieldValueEquals(
      'gateway[paths][negate]',
      // This is the az_cas overridden value.
      $config->get('gateway.paths.negate')
    );
    $this->assertSession()->fieldValueEquals(
      'gateway[paths][pages]',
      // This is the az_cas overridden value.
      $config->get('gateway.paths.pages')
    );
    $this->assertSession()->fieldValueEquals(
      'forced_login[enabled]',
      // This is the az_cas overridden value.
      $config->get('forced_login.enabled')
    );
    $this->assertSession()->fieldValueEquals(
      'forced_login[paths][pages]',
      // This is the az_cas overridden value.
      $config->get('forced_login.paths.pages')
    );
    $this->assertSession()->fieldValueEquals(
      'forced_login[paths][negate]',
      // This is the az_cas overridden value.
      $config->get('forced_login.paths.negate')
    );
    $this->assertSession()->fieldValueEquals(
      'logout[logout_destination]',
      // This is the az_cas overridden value.
      $config->get('logout.logout_destination')
    );
    $this->assertSession()->fieldValueEquals(
      'logout[enable_single_logout]',
      // This is the az_cas overridden value.
      $config->get('logout.enable_single_logout')
    );
    $this->assertSession()->fieldValueEquals(
      'logout[cas_logout]',
      // This is the az_cas overridden value.
      $config->get('logout.cas_logout')
    );
    $this->assertSession()->fieldValueEquals(
      'logout[single_logout_session_lifetime]',
      // This is the az_cas overridden value.
      $config->get('logout.single_logout_session_lifetime')
    );
    $this->assertSession()->fieldValueEquals(
      'proxy[initialize]',
      // This is the az_cas overridden value.
      $config->get('proxy.initialize')
    );
    $this->assertSession()->fieldValueEquals(
      'proxy[can_be_proxied]',
      // This is the az_cas overridden value.
      $config->get('proxy.can_be_proxied')
    );
    $this->assertSession()->fieldValueEquals(
      'proxy[proxy_chains]',
      // This is the az_cas overridden value.
      $config->get('proxy.proxy_chains')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[prevent_normal_login]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.prevent_normal_login')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[auto_register]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.auto_register')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[email_assignment_strategy]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.email_assignment_strategy')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[email_hostname]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.email_hostname')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[email_attribute]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.email_attribute')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[restrict_password_management]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.restrict_password_management')
    );
    $this->assertSession()->fieldValueEquals(
      'user_accounts[restrict_email_management]',
      // This is the az_cas overridden value.
      $config->get('user_accounts.restrict_email_management')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[login_failure_page]',
      // This is the az_cas overridden value.
      $config->get('error_handling.login_failure_page')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_validation_failure]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_validation_failure')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_no_local_account]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_no_local_account')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_subscriber_denied_reg]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_subscriber_denied_reg')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_account_blocked]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_account_blocked')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_subscriber_denied_login]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_subscriber_denied_login')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_username_already_exists]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_username_already_exists')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_prevent_normal_login]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_prevent_normal_login')
    );
    $this->assertSession()->fieldValueEquals(
      'error_handling[message_restrict_password_management]',
      // This is the az_cas overridden value.
      $config->get('error_handling.message_restrict_password_management')
    );
    $this->assertSession()->fieldValueEquals(
      'advanced[debug_log]',
      // This is the az_cas overridden value.
      $config->get('advanced.debug_log')
    );
    $this->assertSession()->fieldValueEquals(
      'advanced[connection_timeout]',
      // This is the az_cas overridden value.
      $config->get('advanced.connection_timeout')
    );
    $this->assertSession()->fieldValueEquals(
      'login_link_enabled',
      // This is the az_cas overridden value.
      $config->get('login_link_enabled')
    );
    $this->assertSession()->fieldValueEquals(
      'login_link_label',
      // This is the az_cas overridden value.
      $config->get('login_link_label')
    );
    $this->assertSession()->fieldValueEquals(
      'login_success_message',
      // This is the az_cas overridden value.
      $config->get('login_success_message')
    );
  }

}
