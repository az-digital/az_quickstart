<?php

namespace Drupal\Tests\az_global_footer\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Quickstart Global Footer.
 */
#[Group('az_global_footer')]
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
