<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Arizona Barrio theme.
 *
 * @group az_barrio
 */
class AzBarrioTest extends BrowserTestBase {

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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a test user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer themes',
      'skip antibot',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test AZ Barrio's defaults.
   */
  public function testThemeDefaults() {
    $this->drupalGet('');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('https://fonts.googleapis.com/css?family=Material+Icons+Sharp');
    $this->assertSession()->responseContains('https://use.typekit.net/emv3zbo.css');
  }

  /**
   * Tests that the Arizona Barrio theme can be uninstalled.
   */
  public function testIsUninstallable() {
    // Login.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Set Bootstrap Barrio as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Arizona Barrio theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The Arizona Barrio theme has been uninstalled.');
  }

  /**
   * Tests that the header column class settings work on install.
   */
  public function testHeaderColumnClassesAreSet() {
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-4');
    $this->assertSession()->elementExists('css', '#header_site > div:nth-child(1) > div > div.col-12.col-sm-6.col-lg-8');
  }

}
