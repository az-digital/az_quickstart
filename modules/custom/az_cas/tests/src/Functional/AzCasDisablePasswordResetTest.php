<?php

namespace Drupal\Tests\az_cas\Functional;

use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that access to the password reset form can be disabled.
 */
#[Group('az_cas')]
#[RunTestsInSeparateProcesses]
class AzCasDisablePasswordResetTest extends QuickstartFunctionalTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * Disable strict schema checking.
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
   * Tests that access to the password reset form is disabled.
   */
  #[DataProvider('azCasSettingsProvider')]
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
  public static function azCasSettingsProvider() {
    return [[FALSE], [TRUE]];
  }

}
