<?php

namespace Drupal\Tests\quick_node_clone\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests node cloning excluding node fields.
 *
 * @group Quick Node Clone
 */
class QuickNodeCloneExcludeNodeFieldsTest extends BrowserTestBase {

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['quick_node_clone'];

  /**
   * A user with the 'Administer quick_node_clone' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'Administer Quick Node Clone Settings',
      'clone page content',
      'create page content',
    ]);

    // Since we don't have ajax here, we need to set the config manually then
    // test the form.
    \Drupal::configFactory()->getEditable('quick_node_clone.settings')
      ->set('exclude.node.page', ['body'])
      ->save();
  }

  /**
   * Test node clone excluding fields.
   */
  public function testNodeCloneExcludeNodeFields() {
    $this->drupalLogin($this->adminUser);

    // Test the form.
    $edit = [
      'text_to_prepend_to_title' => 'Cloned from',
      'bundle_names[page]' => TRUE,
      'page[body]' => TRUE,
    ];
    $this->drupalGet('admin/config/quick-node-clone');
    $this->submitForm($edit, 'Save');

    // Create a basic page.
    $title_value = 'The Original Page';
    $body_value = '<p>This is the original page.</p>';
    $edit = [
      'title[0][value]' => $title_value,
      'body[0][value]' => $body_value,
    ];
    $this->drupalGet('node/add/page');
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains($title_value);
    $this->assertSession()->responseContains($body_value);

    // Clone node.
    $this->clickLink('Clone');
    $node = $this->getNodeByTitle($title_value);
    $this->drupalGet('clone/' . $node->id() . '/quick_clone');
    $this->submitForm([], 'Save');
    $this->assertSession()->responseContains('Cloned from ' . $title_value);
    $this->assertSession()->responseNotContains($body_value);
  }

}
