<?php

namespace Drupal\Tests\extlink\FunctionalJavascript;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Testing of the External Links administration interface and functionality.
 *
 * @group Extlink Admin Tests
 */
class ExtlinkAdminTest extends ExtlinkTestBase {

  use StringTranslationTrait;

  /**
   * Test access to the admin pages.
   */
  public function testAdminAccess(): void {
    $this->drupalLogin($this->normalUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->pageTextContains($this->t('Access denied'));

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->pageTextNotContains($this->t('Access denied'));
  }

  /**
   * Checks to see if external link is disabled on admin routes.
   */
  public function testExtlinkDisabledOnAdminRoutes(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->assertSession()->checkboxNotChecked('extlink_exclude_admin_routes');
    $this->assertSession()->responseContains('/js/extlink.js');

    // Disable Extlink on admin routes.
    $this->drupalGet(self::EXTLINK_ADMIN_PATH);
    $this->submitForm(['extlink_exclude_admin_routes' => TRUE], 'Save configuration');
    $this->assertSession()->responseNotContains('/js/extlink.js');
  }

}
