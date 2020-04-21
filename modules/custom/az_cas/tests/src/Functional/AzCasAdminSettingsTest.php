<?php

namespace Drupal\Tests\az_cas\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests AZ CAS admin settings form.
 *
 * @group az_cas
 */
class AzCasAdminSettingsTest extends BrowserTestBase {

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
  protected $defaultTheme = 'seven';

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser(['administer account settings']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that access to the password reset form is disabled.
   *
   * @dataProvider disablePasswordRecoveryLinkProvider
   */
  public function testPasswordResetBehavior($disable_password_recovery_link) {
    $edit = [
      'disable_password_recovery_link' => $disable_password_recovery_link,
    ];
    $this->drupalPostForm('/admin/config/az-quickstart/settings/az-cas', $edit, 'Save configuration');

    // The menu router info needs to be rebuilt after saving this form so the
    // CAS menu alter runs again.
    $this->container->get('router.builder')->rebuild();

    $this->drupalLogout();
    $this->drupalGet('user/password');
    if ($disable_password_recovery_link) {
      $this->assertSession()->pageTextContains(t('Access denied'));
      $this->assertSession()->pageTextNotContains(t('Reset your password'));
    }
    else {
      $this->assertSession()->pageTextNotContains(t('Access denied'));
      $this->assertSession()->pageTextContains(t('Reset your password'));
    }
  }

  /**
   * Data provider for testPasswordResetBehavior.
   */
  public function disablePasswordRecoveryLinkProvider() {
    return [[FALSE], [TRUE]];
  }

}
