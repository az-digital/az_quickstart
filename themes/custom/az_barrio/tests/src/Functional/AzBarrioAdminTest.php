<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;

/**
 * Tests the Arizona Barrio theme as an admin user.
 *
 * @group az_barrio
 */
class AzBarrioAdminTest extends QuickstartFunctionalTestBase {

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
   * Test AZ Barrio as an admin user.
   */
  public function testAdmin() {
    // Tests that the navbar region classes are set properly.
    // When blocks are removed or added to regions, classes should change.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', '#navbar-top');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.enable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar.has-navigation-region');

    // Tests that the Arizona Barrio theme can be uninstalled.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Set Bootstrap Barrio as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Arizona Barrio theme"]')[0]->click();
    $this->assertSession()->pageTextContains('The Arizona Barrio theme has been uninstalled.');
  }

}
