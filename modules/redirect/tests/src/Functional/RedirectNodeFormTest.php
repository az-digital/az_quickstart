<?php

declare(strict_types=1);

namespace Drupal\Tests\redirect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the redirect functionality on node forms.
 *
 * @group redirect
 */
class RedirectNodeFormTest extends BrowserTestBase {

  /**
   * A normal logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;

  /**
   * A user with permission to bypass content access checks, and add redirects.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'redirect'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create Basic page node type.
    if ($this->profile != 'standard') {
      $this->drupalCreateContentType([
        'type' => 'page',
        'name' => 'Basic page',
        'display_submitted' => FALSE,
      ]);
    }

    $this->webUser = $this->drupalCreateUser([
      'access content',
      'create page content',
      'edit own page content',
    ]);

    $this->adminUser = $this->drupalCreateUser([
      'access content',
      'administer redirects',
      'bypass node access',
    ]);
  }

  /**
   * Test redirect functionality on the node edit form.
   */
  public function testNodeForm() {
    // Login as a regular user.
    $this->drupalLogin($this->webUser);

    // Create "Basic page" content with title.
    $settings = [
      'title' => $this->randomMachineName(8),
    ];
    $node = $this->drupalCreateNode($settings);

    // Load the node edit form.
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Make sure the redirect add button is not visible to this regular user.
    $this->assertSession()->responseNotContains('Add URL redirect');

    // Now edit the same node as an admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Make sure the redirect add button is visible for the admin user.
    $this->assertSession()->responseContains('Add URL redirect');

    // Make sure the link works as expected.
    $this->clickLink('Add URL redirect');
    $this->assertSession()->addressEquals('admin/config/search/redirect/add');
    $this->assertSession()->fieldValueEquals('edit-redirect-redirect-0-uri', '/node/' . $node->id());
  }

}
