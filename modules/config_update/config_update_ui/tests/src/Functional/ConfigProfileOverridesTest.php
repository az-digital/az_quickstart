<?php

namespace Drupal\Tests\config_update_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Verify config reports, reverts, and diffs with profile overrides.
 *
 * @group config_update
 */
class ConfigProfileOverridesTest extends BrowserTestBase {

  /**
   * Use the Standard profile, so that there are profile config overrides.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'config',
    'config_update',
    'config_update_ui',
  ];

  /**
   * The admin user that will be created.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user and log in.
    $this->adminUser = $this->createUser([
      'access administration pages',
      'administer themes',
      'view config updates report',
      'synchronize configuration',
      'export configuration',
      'import configuration',
      'revert configuration',
      'delete configuration',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that config overrides work as expected.
   */
  public function testConfigOverrides() {

    // The Standard install profile contains a system.theme.yml file that
    // sets up olivero/claro as the default/admin theme. The default config
    // from the System module has no admin theme and stark as the default
    // theme. So first, run the report on simple configuration and verify
    // that system.theme is not shown (it should not be missing, or added,
    // or overridden).
    $this->drupalGet('admin/config/development/configuration/report/type/system.simple');
    $session = $this->assertSession();
    $session->responseNotContains('system.theme');

    // Go to the Appearance page and change the theme to whatever is currently
    // disabled. Return to the report and verify that system.theme is there,
    // since it is now overridden.
    $this->drupalGet('admin/appearance');
    $this->clickLink('Install and set as default');
    $this->drupalGet('admin/config/development/configuration/report/type/system.simple');
    $session = $this->assertSession();
    $session->pageTextContains('system.theme');

    // Look at the differences for system.theme and verify it's against
    // the standard profile version, not default version. The line for
    // default should show olivero as the source; if it's against the system
    // version, the word olivero would not be there.
    $this->drupalGet('admin/config/development/configuration/report/diff/system.simple/system.theme');
    $session = $this->assertSession();
    $session->pageTextContains('olivero');

    // Revert and verify that it reverted to the profile version, not the
    // system module version.
    $this->drupalGet('admin/config/development/configuration/report/revert/system.simple/system.theme');
    $this->submitForm([], 'Revert');
    $this->drupalGet('admin/config/development/configuration/single/export/system.simple/system.theme');
    $session = $this->assertSession();
    $session->pageTextContains('admin: claro');
    $session->pageTextContains('default: olivero');
  }

}
