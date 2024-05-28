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
   * Tests that access to the user login form is disabled.
   *
   * @dataProvider azCasSettingsProvider
   */
  public function testUserLoginFormBehavior($disable_login_form) {
    $edit = [
      'disable_login_form' => $disable_login_form,
    ];
    $this->drupalGet('/admin/config/az-quickstart/settings/az-cas');
    $this->submitForm($edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // routeSubscriber runs again.
    $this->container->get('router.builder')->rebuild();

    // Logout manually because $this->drupalLogout() checks for prescence of
    // fields on login form which don't exist if login form is disabled.
    $destination = Url::fromRoute('<front>')->toString();
    $this->drupalGet(Url::fromRoute('user.logout', [], ['query' => ['destination' => $destination]]));

    $this->drupalGet('user/login');
    if ($disable_login_form) {
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
   * Tests that access to the password reset form is disabled.
   *
   * @dataProvider azCasSettingsProvider
   */
  public function testPasswordResetBehavior($disable_password_recovery_link) {
    $edit = [
      'disable_password_recovery_link' => $disable_password_recovery_link,
    ];
    $this->drupalGet('/admin/config/az-quickstart/settings/az-cas');
    $this->submitForm($edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // routeSubscriber runs again.
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogout();
    $this->drupalGet('user/password');
    if ($disable_password_recovery_link) {
      $this->assertSession()->pageTextContains('Access denied');
      $this->assertSession()->pageTextNotContains('Reset your password');
    }
    else {
      $this->assertSession()->pageTextNotContains('Access denied');
      $this->assertSession()->pageTextContains('Reset your password');
    }
  }

  /**
   * Data provider for testUserLoginFormBehavior and testPasswordResetBehavior.
   */
  public function azCasSettingsProvider() {
    return [[FALSE], [TRUE]];
  }

}
