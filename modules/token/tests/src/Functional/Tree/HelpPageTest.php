<?php

namespace Drupal\Tests\token\Functional\Tree;

use Drupal\Tests\token\Functional\TokenTestBase;

/**
 * Tests token tree on help page.
 *
 * @group token
 */
class HelpPageTest extends TokenTestBase {

  use TokenTreeTestTrait;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['help'];

  public function setUp(): void {
    parent::setUp();

    // On Drupal 10.2 there is new permission to access help pages.
    $permissions = ['access help pages'];
    if (version_compare(\Drupal::VERSION, '10.2', '<')) {
      $permissions = ['access administration pages'];
    }
    $this->account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests the token browser on the token help page.
   */
  public function testHelpPageTree() {
    $this->drupalGet('admin/help/token');
    $this->assertSession()->pageTextContains('The list of the currently available tokens on this site are shown below.');

    $this->assertTokenGroup('Current date');
    $this->assertTokenGroup('Site information');

    $this->assertTokenInTree('[current-date:html_date]', 'current-date');
    $this->assertTokenInTree('[current-date:html_week]', 'current-date');
    $this->assertTokenInTree('[date:html_date]', 'date');
    $this->assertTokenInTree('[date:html_week]', 'date');

    $this->assertTokenInTree('[current-user:account-name]', 'current-user');
    $this->assertTokenInTree('[user:account-name]', 'user');

    $this->assertTokenInTree('[current-page:url:unaliased]', 'current-page--url');
    $this->assertTokenInTree('[current-page:url:unaliased:args]', 'current-page--url--unaliased');
    $this->assertTokenInTree('[user:original:account-name]', 'user--original');

    // Assert some of the restricted tokens to ensure they are shown.
    $this->assertTokenInTree('[user:one-time-login-url]', 'user');
    $this->assertTokenInTree('[user:original:cancel-url]', 'user--original');

    // The Array token is marked as nested, so it should not show up as a top
    // level token, only nested under another token. For instance, user:roles
    // is of type Array and tokens of type Array have 'nested' setting true.
    $this->assertTokenNotGroup('Array');
    $this->assertTokenNotGroup('user:roles');
    $this->assertTokenInTree('[user:roles]', 'user');
  }

}
