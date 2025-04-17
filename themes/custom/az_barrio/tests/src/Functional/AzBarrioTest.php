<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;

/**
 * Tests the Arizona Barrio theme.
 *
 * @group az_barrio
 */
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
   * The created user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'administer blocks',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test AZ Barrio as an anonymous user.
   */
  public function testAnonymous() {
    // Test AZ Barrio's defaults.
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('https://fonts.googleapis.com/css?family=Material+Icons+Sharp');
    $this->assertSession()->responseContains('https://use.typekit.net/emv3zbo.css');

    // Tests that the header column class settings work on install.
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-4');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-8');

    // Tests that the navbar off-canvas region classes are set on install.
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar-offcanvas.has-navigation-region.has-off-canvas-region');
  }

  /**
   * Test AZ Barrio as an admin user.
   */
  public function testAdmin() {
    // Login.
    $this->drupalLogin($this->adminUser);

    // Tests that the navbar off-canvas region classes are set properly.
    // When blocks are removed or added to regions, classes should change.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-offcanvas-searchform-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar-offcanvas.has-navigation-region.no-off-canvas-region');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar-offcanvas.no-navigation-region.no-off-canvas-region');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-offcanvas-searchform-operations"] li.enable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar-offcanvas.no-navigation-region.has-off-canvas-region');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.enable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar-offcanvas.has-navigation-region.has-off-canvas-region');

    // Tests that the Arizona Barrio theme can be uninstalled.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Set Bootstrap Barrio as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Arizona Barrio theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The Arizona Barrio theme has been uninstalled.');
  }

}
