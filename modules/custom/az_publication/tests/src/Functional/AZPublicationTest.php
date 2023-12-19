<?php

namespace Drupal\Tests\az_publication\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Quickstart Publication module.
 *
 * @group az_publication
 */
class AZPublicationTest extends BrowserTestBase {

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'az_quickstart';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $modules = [
    'az_publication',
  ];

  /**
   * Disable strict schema cheking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Tests that the Quickstart Publication module can be reinstalled.
   *
   * @group az_publication
   */
  public function testIsUninstallableAndReinstallable() {

    // Uninstalls the az_publication module, so hook_modules_uninstalled()
    // is executed.
    $this->container
      ->get('module_installer')
      ->uninstall([
        'az_publication',
      ]);

    // Reinstalls the az_publication module.
    $this->container
      ->get('module_installer')
      ->install([
        'az_publication',
      ]);

  }

}
