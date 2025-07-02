<?php

namespace Drupal\Tests\pathauto\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Views;

/**
 * Tests pathauto user UI integration.
 *
 * @group pathauto
 */
class PathautoUserWebTest extends BrowserTestBase {

  use PathautoTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['pathauto', 'views'];

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Allow other modules to add additional permissions for the admin user.
    $permissions = [
      'administer pathauto',
      'administer url aliases',
      'bulk delete aliases',
      'bulk update aliases',
      'create url aliases',
      'administer users',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->drupalLogin($this->adminUser);

    $this->createPattern('user', '/users/[user:name]');
  }

  /**
   * Basic functional testing of Pathauto with users.
   */
  public function testUserEditing() {
    // There should be no Pathauto checkbox on user forms.
    $this->drupalGet('user/' . $this->adminUser->id() . '/edit');
    $this->assertSession()->fieldValueNotEquals('path[0][pathauto]', '');
  }

  /**
   * Test user operations.
   */
  public function testUserOperations() {
    $account = $this->drupalCreateUser();

    // Delete all current URL aliases.
    $this->deleteAllAliases();

    // Find the position of just created account in the user_admin_people view.
    $view = Views::getView('user_admin_people');
    $view->initDisplay();
    $view->preview('page_1');

    foreach ($view->result as $key => $row) {
      if ($view->field['name']->getValue($row) == $account->getDisplayName()) {
        break;
      }
    }

    $edit = [
      'action' => 'pathauto_update_alias_user',
      "user_bulk_form[$key]" => TRUE,
    ];
    $this->drupalGet('admin/people');
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->pageTextContains('Update URL alias was applied to 1 item.');

    $this->assertEntityAlias($account, '/users/' . mb_strtolower($account->getDisplayName()));
    $this->assertEntityAlias($this->adminUser, '/user/' . $this->adminUser->id());
  }

}
