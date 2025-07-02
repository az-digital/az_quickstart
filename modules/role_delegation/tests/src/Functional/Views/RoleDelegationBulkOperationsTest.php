<?php

namespace Drupal\Tests\role_delegation\Functional\Views;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\system\Entity\Action;
use Drupal\Tests\BrowserTestBase;
use Drupal\views\Entity\View;

/**
 * Functional tests for assigning roles in vbo.
 *
 * @group role_delegation
 */
class RoleDelegationBulkOperationsTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['action', 'user', 'role_delegation', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test if a user is able to edit the allowed roles in VBO.
   */
  public function testVboRoleDelegation(): void {
    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);
    $rid3 = $this->drupalCreateRole([]);

    // User that can assign all roles.
    $account = $this->createUser(['administer users', 'assign all roles']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid3));

    // User that can assign only role 1.
    $account = $this->createUser([
      'administer users',
      sprintf('assign %s role', $rid1),
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertSession()->optionNotExists('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertSession()->optionNotExists('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertSession()->optionNotExists('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertSession()->optionNotExists('action', sprintf('user_remove_role_action.%s', $rid3));

    // User that can assign role 2 and role 3.
    $account = $this->createUser([
      'administer users',
      sprintf('assign %s role', $rid2),
      sprintf('assign %s role', $rid3),
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/people');
    $this->assertSession()->optionNotExists('action', sprintf('user_add_role_action.%s', $rid1));
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid2));
    $this->assertSession()->optionExists('action', sprintf('user_add_role_action.%s', $rid3));
    $this->assertSession()->optionNotExists('action', sprintf('user_remove_role_action.%s', $rid1));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid2));
    $this->assertSession()->optionExists('action', sprintf('user_remove_role_action.%s', $rid3));
  }

  /**
   * Test VBO still works without the "administer users" permission.
   */
  public function testVboRoleDelegationWithoutAdministerUsersPermission(): void {
    /** @var \Drupal\views\Entity\View $view */
    $view = View::load('user_admin_people');
    $display = &$view->getDisplay('default');

    $display['display_options']['access']['options']['perm'] = 'access administration pages';
    $view->save();

    $this->container->get('router.builder')->rebuildIfNeeded();

    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);

    $account = $this->createUser([
      'access administration pages',
      sprintf('assign %s role', $rid1),
      sprintf('assign %s role', $rid2),
    ]);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/people');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm([
      'action' => 'user_add_role_action.' . $rid1,
      'user_bulk_form[1]' => TRUE,
    ],
      'Apply to selected items'
    );

    $add_action = Action::load('user_add_role_action.' . $rid1);
    $this->assertSession()->responseNotContains(new FormattableMarkup('No access to execute %action on the @entity_type_label %entity_label.', [
      '%action' => $add_action->label(),
      '@entity_type_label' => 'User',
      '%entity_label' => $account->label(),
    ]));
    $this->assertSession()->responseContains(new FormattableMarkup('%action was applied to @count item.', [
      '%action' => $add_action->label(),
      '@count' => 1,
    ]));

    $this->submitForm([
      'action' => 'user_remove_role_action.' . $rid2,
      'user_bulk_form[1]' => TRUE,
    ],
      'Apply to selected items'
    );

    $remove_action = Action::load('user_remove_role_action.' . $rid2);
    $this->assertSession()->responseNotContains(new FormattableMarkup('No access to execute %action on the @entity_type_label %entity_label.', [
      '%action' => $remove_action->label(),
      '@entity_type_label' => 'User',
      '%entity_label' => $account->label(),
    ]));
    $this->assertSession()->responseContains(new FormattableMarkup('%action was applied to @count item.', [
      '%action' => $remove_action->label(),
      '@count' => 1,
    ]));
  }

}
