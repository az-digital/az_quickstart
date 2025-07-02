<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that Paragraphs module can be uninstalled.
 *
 * @group paragraphs
 */
class ParagraphsUninstallTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array('paragraphs_demo');

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $admin_user = $this->drupalCreateUser(array(
      'administer paragraphs types',
      'administer modules',
    ));
    $this->drupalLogin($admin_user);
  }

  /**
   * Tests that Paragraphs module can be uninstalled.
   */
  public function testUninstall() {

    // Uninstall the module paragraphs_demo.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[paragraphs_demo]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // Delete library data.
    $this->clickLink('Remove Paragraphs library items');
    $this->submitForm([], 'Delete all Paragraphs library items');

    // Uninstall the library module.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[paragraphs_library]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');

    // Delete paragraphs data.
    $this->clickLink('Remove Paragraphs');
    $this->submitForm([], 'Delete all Paragraphs');

    // Uninstall the module paragraphs.
    $this->drupalGet('admin/modules/uninstall');
    $this->submitForm(['uninstall[paragraphs]' => TRUE], 'Uninstall');
    $this->submitForm([], 'Uninstall');
    $this->assertSession()->pageTextContains('The selected modules have been uninstalled.');
    $this->assertSession()->pageTextNotContains('Paragraphs demo');
    $this->assertSession()->pageTextNotContains('Paragraphs library');
    $this->assertSession()->pageTextNotContains('Paragraphs');
  }

}
