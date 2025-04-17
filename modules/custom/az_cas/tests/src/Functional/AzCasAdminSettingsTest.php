<?php

namespace Drupal\Tests\az_cas\Functional;

use Drupal\Core\Url;
use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;

/**
 * Tests AZ CAS admin settings form.
 *
 * @group az_cas
 */
class AzCasAdminSettingsTest extends QuickstartFunctionalTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * Disable strict schema cheking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The default theme to use for testing.
   *
   * @var string
   */
  protected $defaultTheme = 'claro';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that custom AZ CAS settings work correctly.
   *
   * @dataProvider azCasSettingsProvider
   */
  public function testAzCazSettings($disable_setting) {
    // Tests that access to the password reset form can be disabled.
    $edit = [
      'disable_password_recovery_link' => $disable_setting,
    ];
    $this->drupalGet('/admin/config/az-quickstart/settings/az-cas');
    $this->submitForm($edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // routeSubscriber runs again.
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogout();
    $this->drupalGet('user/password');
    if ($disable_setting) {
      $this->assertSession()->pageTextContains('Access denied');
      $this->assertSession()->pageTextNotContains('Reset your password');
    }
    else {
      $this->assertSession()->pageTextNotContains('Access denied');
      $this->assertSession()->pageTextContains('Reset your password');
    }

    // Tests that access to the user login form can be disabled.
    $edit = [
      'disable_login_form' => $disable_setting,
    ];
    $this->drupalGet('/admin/config/az-quickstart/settings/az-cas');
    $this->submitForm($edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // routeSubscriber runs again.
    $this->container->get('router.builder')->rebuild();

    // Logout manually because $this->drupalLogout() checks for prescence of
    // fields on login form which don't exist if login form is disabled.
    $destination = Url::fromRoute('<front>')->toString();
    $this->drupalGet(Url::fromRoute('user.logout.confirm', options: ['query' => ['destination' => $destination]]));
    $this->submitForm([], 'Log out');

    $this->drupalGet('user/login');
    if ($disable_setting) {
      $this->assertSession()->pageTextNotContains('Username');
      $this->assertSession()->pageTextNotContains('Password');
      $this->assertSession()->buttonNotExists('Log in');
    }
    else {
      $this->assertSession()->pageTextContains('Username');
      $this->assertSession()->pageTextContains('Password');
      $this->assertSession()->buttonExists('Log in');
    }
  }

  /**
   * Data provider for testUserLoginFormBehavior and testPasswordResetBehavior.
   */
  public static function azCasSettingsProvider() {
    return [[FALSE], [TRUE]];
  }

}
