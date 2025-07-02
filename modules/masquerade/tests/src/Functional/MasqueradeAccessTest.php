<?php

namespace Drupal\Tests\masquerade\Functional;

use Drupal\Core\Session\AccountInterface;

/**
 * Tests masquerade access mechanism.
 *
 * @todo Convert into DUTB. This is essentially a unit test for
 *   masquerade_target_user_access() only.
 *
 * @group masquerade
 */
class MasqueradeAccessTest extends MasqueradeWebTestBase {

  /**
   * Tests masquerade access for different source and target users.
   *
   * Test plan summary:
   * - root » admin
   * - admin » root
   * - admin » moderator (more roles but less privileges)
   * - admin » super (administrator and editor roles)
   * - admin » lead (editor roles)
   * - admin » masquerade (different role)
   * - admin » auth (less roles)
   * - moderator ! root
   * - moderator ! admin (less roles but more privileges)
   * - moderator ! editor (different roles + privileges)
   * - moderator » super (administrator and editor roles)
   * - moderator » lead (editor roles)
   * - moderator » auth
   * - [editor is access-logic-wise equal to moderator, so skipped]
   * - masquerade ! root
   * - masquerade ! admin (different role with more privileges)
   * - masquerade ! moderator (more roles)
   * - masquerade ! lead (editor roles)
   * - masquerade ! super (administrator and editor roles)
   * - masquerade » auth
   * - masquerade ! masquerade (self)
   * - lead ! root
   * - lead ! admin (different role with more privileges)
   * - lead ! moderator (more roles)
   * - lead ! super (administrator and editor roles)
   * - lead » editor
   * - lead » auth
   * - auth ! *
   */
  public function testAccess() {
    $this->drupalLogin($this->rootUser);
    $this->assertCanMasqueradeAs($this->adminUser);

    $this->drupalLogin($this->adminUser);
    // Permission 'masquerade as super user' granted by default.
    $this->assertCanMasqueradeAs($this->rootUser);

    // Permission 'masquerade as any user' granted by default.
    $this->assertCanMasqueradeAs($this->moderatorUser);
    $this->assertCanMasqueradeAs($this->superUser);
    $this->assertCanMasqueradeAs($this->leadEditorUser);
    $this->assertCanMasqueradeAs($this->editorUser);
    $this->assertCanMasqueradeAs($this->masqueradeUser);
    $this->assertCanMasqueradeAs($this->authUser);

    // Test 'masquerade as any user' permission except UID 1.
    $this->drupalLogin($this->moderatorUser);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanMasqueradeAs($this->adminUser);
    $this->assertCanMasqueradeAs($this->superUser);
    $this->assertCanMasqueradeAs($this->leadEditorUser);
    $this->assertCanMasqueradeAs($this->editorUser);
    $this->assertCanMasqueradeAs($this->masqueradeUser);
    $this->assertCanMasqueradeAs($this->authUser);

    // Test 'masquerade as @role' permission.
    $this->drupalLogin($this->editorUser);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanNotMasqueradeAs($this->adminUser);
    $this->assertCanNotMasqueradeAs($this->moderatorUser);
    $this->assertCanNotMasqueradeAs($this->superUser);
    $this->assertCanNotMasqueradeAs($this->leadEditorUser);
    $this->assertCanMasqueradeAs($this->masqueradeUser);
    $this->assertCanMasqueradeAs($this->authUser);

    // Test 'masquerade as @role' permission.
    $this->drupalLogin($this->leadEditorUser);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanNotMasqueradeAs($this->adminUser);
    $this->assertCanNotMasqueradeAs($this->moderatorUser);
    $this->assertCanNotMasqueradeAs($this->superUser);
    $this->assertCanNotMasqueradeAs($this->masqueradeUser);
    $this->assertCanMasqueradeAs($this->editorUser);
    $this->assertCanMasqueradeAs($this->authUser);

    // Test 'masquerade as authenticated' permission.
    $this->drupalLogin($this->masqueradeUser);
    $this->assertCanNotMasqueradeAs($this->rootUser);
    $this->assertCanNotMasqueradeAs($this->adminUser);
    $this->assertCanNotMasqueradeAs($this->moderatorUser);
    $this->assertCanNotMasqueradeAs($this->superUser);
    $this->assertCanNotMasqueradeAs($this->leadEditorUser);
    $this->assertCanNotMasqueradeAs($this->editorUser);
    $this->assertCanMasqueradeAs($this->authUser);

    // Verify that a user cannot masquerade as himself.
    $edit = [
      'masquerade_as' => $this->masqueradeUser->getAccountName(),
    ];
    $this->drupalGet('masquerade');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Switch');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->responseContains($this->t('You cannot masquerade as yourself. Please choose a different user to masquerade as.'));
    $this->assertSession()->pageTextNotContains('Unmasquerade');

    // Basic 'masquerade' permission check.
    $this->drupalLogin($this->authUser);
    $this->drupalGet('masquerade');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Asserts that the logged-in user can masquerade as a given target user.
   *
   * @param \Drupal\Core\Session\AccountInterface $target_account
   *   The user to masquerade to.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function assertCanMasqueradeAs(AccountInterface $target_account) {
    $edit = [
      'masquerade_as' => $target_account->getAccountName(),
    ];
    $this->drupalGet('masquerade');
    // This will return a 200 response because the visitor will be able to load
    // the form.
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Switch');
    // This will return a 403 response because the visitor will not be able to
    // load the form after switching.
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()
      ->responseNotContains($this->t('You are not allowed to masquerade as %name.', [
        '%name' => $target_account->getDisplayName(),
      ]));
    $this->clickLink('Unmasquerade');
  }

  /**
   * Asserts that the logged-in user can not masquerade as a given target user.
   *
   * @param \Drupal\Core\Session\AccountInterface $target_account
   *   The user to masquerade to.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function assertCanNotMasqueradeAs(AccountInterface $target_account) {
    $edit = [
      'masquerade_as' => $target_account->getAccountName(),
    ];
    $this->drupalGet('masquerade');
    // This will return a 200 response because the visitor will be able to load
    // the form.
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm($edit, 'Switch');
    // This will return a 200 response because the visitor will not have
    // switched to the other account.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->responseContains($this->t('You are not allowed to masquerade as %name.', [
        '%name' => $target_account->getDisplayName(),
      ]));
    $this->assertSession()->pageTextNotContains('Unmasquerade');
  }

}
