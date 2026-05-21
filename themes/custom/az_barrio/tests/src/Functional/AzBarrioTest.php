<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Arizona Barrio theme.
 */
#[Group('az_barrio')]
#[RunTestsInSeparateProcesses]
class AzBarrioTest extends QuickstartFunctionalTestBase {

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
   * Test AZ Barrio as an anonymous user.
   */
  public function testAnonymous() {
    // Test AZ Barrio's defaults.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,1,0');
    $this->assertSession()->responseContains('https://use.typekit.net/emv3zbo.css');

    // Tests that the header column class settings work on install.
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-4');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-8');

    // Tests that navigation elements are present on install.
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar.navbar-expand');
    $this->assertSession()->elementExists('css', '#block-az-barrio-offcanvas-searchform');
    $this->assertSession()->elementExists('css', '#block-az-barrio-mobilenavblock');
  }

}
