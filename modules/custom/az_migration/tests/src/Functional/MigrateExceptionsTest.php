<?php

namespace Drupal\Tests\az_migration\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Quickstart Global Footer.
 *
 * @group az_global_footer
 */
class MigrateExceptionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'az_quickstart';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'az_barrio';

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
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
  }

}
