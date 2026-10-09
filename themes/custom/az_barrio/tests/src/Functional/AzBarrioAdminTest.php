<?php

namespace Drupal\Tests\az_barrio\Functional;

use Drupal\block\Entity\Block;
use Drupal\Tests\az_core\Functional\QuickstartFunctionalTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Arizona Barrio theme as an admin user.
 */
#[Group('az_barrio')]
#[RunTestsInSeparateProcesses]
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
   * @var \Drupal\user\UserInterface
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
    // Tests that navigation elements are correctly displayed.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', '#navbar-top');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-main-menu-operations"] li.enable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#navbar-top.navbar.navbar-expand.navbar-az');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-offcanvas-searchform-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', '#jsAzSearch');

    Block::create([
      'id' => 'test_offcanvas_searchform',
      'theme' => 'az_barrio',
      'region' => 'navigation_offcanvas',
      'plugin' => 'search_form_block',
      'weight' => 0,
      'status' => 1,
      'visibility' => [],
      'settings' => [
        'id' => 'search_form_block',
        'label' => 'Search',
        'label_display' => FALSE,
        'provider' => 'search',
        'page_id' => '',
      ],
    ])->save();

    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '#jsAzSearch');
    $this->assertSession()->elementExists('css', '#block-test-offcanvas-searchform');
    $this->assertSession()->responseContains('themes/custom/az_barrio/js/az-barrio-off-canvas-nav.js');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-mobilenavblock-operations"] li.disable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementNotExists('css', '.arizona-header > .container > .row > div > [data-bs-target="#azMobileNav"]:not(#jsAzSearch)');
    $this->drupalGet('admin/structure/block');
    $this->cssSelect('ul[data-drupal-selector="edit-blocks-az-barrio-mobilenavblock-operations"] li.enable a')[0]->click();
    $this->drupalGet('');
    $this->assertSession()->elementExists('css', '.arizona-header > .container > .row > div > [data-bs-target="#azMobileNav"]:not(#jsAzSearch)');

    // Tests that the Arizona Barrio theme can be uninstalled.
    $this->drupalGet('admin/appearance');
    $this->cssSelect('a[title="Set Bootstrap Barrio as default theme"]')[0]->click();
    $this->cssSelect('a[title="Uninstall Arizona Barrio theme"]')[0]->click();
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The Arizona Barrio theme has been uninstalled.');
  }

}
