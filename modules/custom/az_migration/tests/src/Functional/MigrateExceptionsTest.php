<?php

namespace Drupal\Tests\az_migration\Functional;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Quickstart Global Footer.
 */
#[Group('az_global_footer')]
class MigrateExceptionsTest extends BrowserTestBase {

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
   * Disable strict schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = ['az_migration'];

  /**
   * Tests that the Quickstart Global Footer module can be installed.
   *
   * @group regression
   */
  public function testGlobalFooterMigration() {
    // Install the az_global_footer module.
    $this->container
      ->get('module_installer')
      ->install([
        'az_global_footer',
      ]);
    $this->assertSession()->statusCodeEquals(200);
  }

}
