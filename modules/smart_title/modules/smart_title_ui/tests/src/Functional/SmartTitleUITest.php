<?php

namespace Drupal\Tests\smart_title_ui\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Smart Title UI.
 *
 * @group smart_title
 * @group smart_title_ui
 */
class SmartTitleUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'smart_title_ui',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a node type.
    $this->drupalCreateContentType([
      'type' => 'test_page',
    ]);
  }

  /**
   * Tests the EntityViewMode user interface.
   */
  public function testSmartTitleUserInterface() {
    // Test the listing page.
    $config_url = Url::fromRoute('smart_title_ui.settings');
    // No access for anonymous users (without the 'administer smart title')
    // permission.
    $this->drupalGet($config_url);
    $this->assertSession()->statusCodeEquals(403);

    // Allow access with (only) the 'administer smart title' permission.
    $this->drupalLogin($this->drupalCreateUser(['administer smart title']));
    $this->drupalGet($config_url);
    $web_assert = $this->assertSession();
    $web_assert->statusCodeEquals(200);
    $web_assert->pageTextContains('test_page');

    // Validate empty Smart Title settings.
    $smart_title_bundles = $this->config('smart_title.settings')->get('smart_title');
    $this->assertTrue(empty($smart_title_bundles));
    $page = $this->getSession()->getPage();
    $page->hasUncheckedField('test_page');

    // Set up Smart Title for test node.
    $this->submitForm([
      'node_bundles[node:test_page]' => 1,
    ], 'Save configuration');

    // Verify that the config was saved.
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $page->hasCheckedField('test_page');
    $smart_title_bundles = $this->config('smart_title.settings')->get('smart_title');
    $this->assertTrue(['node:test_page'] === $smart_title_bundles);

    // Uncheck test_page.
    $this->submitForm([
      'node_bundles[node:test_page]' => 0,
    ], 'Save configuration');

    // Verify that the empty config can be saved.
    $page = $this->getSession()->getPage();
    $this->assertSession()->pageTextContains('The configuration options have been saved.');
    $page->hasUncheckedField('test_page');
    $smart_title_bundles = $this->config('smart_title.settings')->get('smart_title');
    $this->assertTrue(empty($smart_title_bundles));
  }

}
