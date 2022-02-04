<?php

namespace Drupal\Tests\az_global_footer\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Quickstart Global Footer.
 *
 * @group az_global_footer
 */
class AzGlobalFooterTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * Disable strict schema cheking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The created user.
   *
   * @var User
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */

  public static $modules = ['az_global_footer'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer modules',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the Quickstart Global Footer module can be uninstalled.
   *
   * @group regression
   */
  public function testIsUninstallableAndReinstallable() {

    // Uninstalls the az_global_footer module, so hook_modules_uninstalled()
    // is executed.
    $this->container
      ->get('module_installer')
      ->uninstall([
        'az_global_footer',
      ]);
    // Reinstalls the az_global_footer module.
    $this->container
      ->get('module_installer')
      ->install([
        'az_global_footer',
      ]);

  }

}
