<?php

namespace Drupal\Tests\az_select_menu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Quickstart select menu block.
 *
 * @group az_select_menu
 */
class AzSelectMenuTest extends BrowserTestBase {

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
   * Tests that the Quickstart Select Menu Block module can be uninstalled.
   *
   * @group az_select_menu
   */
  public function testIsUninstallableAndReinstallable() {

    // Uninstalls the az_select_menu module, so hook_modules_uninstalled()
    // is executed.
    $this->container
      ->get('module_installer')
      ->uninstall([
        'az_select_menu',
      ]);

    // Reinstalls the az_select_menu module.
    $this->container
      ->get('module_installer')
      ->install([
        'az_select_menu',
      ]);

  }

}
