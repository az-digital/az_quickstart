<?php

namespace Drupal\Tests\smart_title\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Base class to provide common test setup for Smart Title functional tests.
 */
abstract class SmartTitleBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'field_ui',
    'node',
    'smart_title',
    'views',
  ];

  /**
   * An administrative user for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Test page node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testPageNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('system_main_block');

    $this->config('system.site')
      ->set('page.front', '/node')
      ->save();

    // Create test_block and test_page node types.
    $this->drupalCreateContentType([
      'type' => 'test_page',
      'name' => 'Test page',
      'display_submitted' => FALSE,
    ]);

    // Add Smart Title for test_page.
    $this->config('smart_title.settings')
      ->set('smart_title', ['node:test_page'])
      ->save();
    $this->rebuildAll();

    // Add test node.
    $this->testPageNode = $this->drupalCreateNode(['type' => 'test_page']);

    // Create users and test node.
    $this->adminUser = $this->drupalCreateUser([
      'access content overview',
      'access content',
      'administer site configuration',
      'administer content types',
      'administer display modes',
      'administer node display',
      'administer node fields',
      'administer node form display',
      'administer nodes',
      'create test_page content',
    ]);
  }

}
