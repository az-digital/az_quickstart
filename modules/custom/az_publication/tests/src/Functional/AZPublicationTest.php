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
  protected static $modules = [
    'az_publication',
    'az_publication_bibtex',
    'az_publication_doi',
    'az_publication_import',
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

    // Uninstalls the az_publication modules, so `az_publication_uninstall()`
    // is executed.
    $this->container
      ->get('module_installer')
      ->uninstall([
        'az_publication',
        'az_publication_bibtex',
        'az_publication_doi',
        'az_publication_import',
      ]);

    // Reinstalls the az_publication modules.
    $this->container
      ->get('module_installer')
      ->install([
        'az_publication',
        'az_publication_bibtex',
        'az_publication_doi',
        'az_publication_import',
      ]);

  }

}
