<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Core\Url;

/**
 * Tests devel menu links.
 *
 * @group devel
 */
class DevelMenuLinksTest extends DevelBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Devel links currently appears only in the devel menu.
    // Place the devel menu block so we can ensure that these link works
    // properly.
    $this->drupalPlaceBlock('system_menu_block:devel');
    $this->drupalPlaceBlock('page_title_block');
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests CSFR protected links.
   */
  public function testCsrfProtectedLinks(): void {
    // Ensure CSRF link are not accessible directly.
    $this->drupalGet('devel/run-cron');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('devel/cache/clear');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure clear cache link works properly.
    $this->assertSession()->linkExists('Cache clear');
    $this->clickLink('Cache clear');
    $this->assertSession()->pageTextContains('Cache cleared.');

    // Ensure run cron link works properly.
    $this->assertSession()->linkExists('Run cron');
    $this->clickLink('Run cron');
    $this->assertSession()->pageTextContains('Cron ran successfully.');

    // Ensure CSRF protected links work properly after change session.
    $this->drupalLogout();
    $this->drupalLogin($this->adminUser);

    $this->assertSession()->linkExists('Cache clear');
    $this->clickLink('Cache clear');
    $this->assertSession()->pageTextContains('Cache cleared.');

    $this->assertSession()->linkExists('Run cron');
    $this->clickLink('Run cron');
    $this->assertSession()->pageTextContains('Cron ran successfully.');
  }

  /**
   * Tests redirect destination links.
   */
  public function testRedirectDestinationLinks(): void {
    // By default, in the testing profile, front page is the user canonical URI.
    // For better testing do not use the default frontpage.
    $url = Url::fromRoute('devel.simple_page');

    $this->drupalGet($url);
    $this->assertSession()->linkExists('Reinstall Modules');
    $this->clickLink('Reinstall Modules');
    $this->assertSession()->addressEquals('devel/reinstall');

    $this->drupalGet($url);
    $this->assertSession()->linkExists('Rebuild Menu');
    $this->clickLink('Rebuild Menu');
    $this->assertSession()->addressEquals('devel/menu/reset');

    $this->drupalGet($url);
    $this->assertSession()->linkExists('Cache clear');
    $this->clickLink('Cache clear');
    $this->assertSession()->pageTextContains('Cache cleared.');
    $this->assertSession()->addressEquals($url->toString());

    $this->drupalGet($url);
    $this->assertSession()->linkExists('Run cron');
    $this->clickLink('Run cron');
    $this->assertSession()->pageTextContains('Cron ran successfully.');
    $this->assertSession()->addressEquals($url->toString());
  }

}
