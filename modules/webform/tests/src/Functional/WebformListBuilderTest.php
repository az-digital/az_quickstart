<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Tests for webform list builder.
 *
 * @group webform
 */
class WebformListBuilderTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Tests the webform overview filter and limit.
   */
  public function testFilterAndLimit() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check filter default category and state.
    $this->drupalGet('/admin/structure/webform');
    $this->assertTrue($assert_session->optionExists('edit-category', '')->hasAttribute('selected'));
    $this->assertTrue($assert_session->optionExists('edit-state', '')->hasAttribute('selected'));

    // Set filter category and state.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('form.filter_category', 'Test: Submissions')
      ->set('form.filter_state', 'open')
      ->save();

    // Check filter customized category and state.
    $this->drupalGet('/admin/structure/webform');
    $this->assertTrue($assert_session->optionExists('edit-category', 'Test: Submissions')->hasAttribute('selected'));
    $this->assertTrue($assert_session->optionExists('edit-state', 'open')->hasAttribute('selected'));

    // Check customized filter can still be cleared.
    $this->drupalGet('/admin/structure/webform', ['query' => ['category' => '', 'state' => '']]);
    $this->assertTrue($assert_session->optionExists('edit-category', '')->hasAttribute('selected'));
    $this->assertTrue($assert_session->optionExists('edit-state', '')->hasAttribute('selected'));

    // Clear the filters.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('form.filter_category', '')
      ->set('form.filter_state', '')
      ->save();

    // Check that two webforms are displayed when the limit is 50.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->fieldExists('items[contact]');
    $assert_session->fieldExists('items[test_submissions]');
    $this->assertNoCssSelect('.pager');

    // Create 1 extra webform and set the limit to 1.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('form.limit', 1)
      ->save();

    // Check the now only one webform is displayed.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->fieldExists('items[contact]');
    $assert_session->fieldNotExists('items[test_submissions]');
    $this->assertCssSelect('.pager');
  }

  /**
   * Tests the webform overview bulk operations.
   */
  public function testBulkOperations() {
    $assert_session = $this->assertSession();

    // Add three test webforms.
    /** @var \Drupal\webform\Entity\Webform[] $webforms */
    $webforms = [
      $this->createWebform(['id' => 'one']),
      $this->createWebform(['id' => 'two']),
      $this->createWebform(['id' => 'three']),
    ];

    $this->drupalLogin($this->rootUser);

    // Check bulk operation access.
    $this->drupalGet('/admin/structure/webform');
    $this->assertCssSelect('#webform-bulk-form');
    $this->assertCssSelect('#edit-items-one');
    $this->assertCssSelect('#edit-items-two');
    $this->assertCssSelect('#edit-items-three');

    // Check available actions when NOT filtered by archived webforms.
    $this->drupalGet('/admin/structure/webform');
    $this->assertCssSelect('option[value="webform_open_action"]');
    $this->assertCssSelect('option[value="webform_close_action"]');
    $this->assertCssSelect('option[value="webform_archive_action"]');
    $this->assertNoCssSelect('option[value="webform_unarchive_action"]');
    $this->assertCssSelect('option[value="webform_delete_action"]');

    // Check available actions when filtered by archived webforms.
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $this->assertNoCssSelect('option[value="webform_open_action"]');
    $this->assertNoCssSelect('option[value="webform_close_action"]');
    $this->assertNoCssSelect('option[value="webform_archive_action"]');
    $this->assertCssSelect('option[value="webform_unarchive_action"]');
    $this->assertCssSelect('option[value="webform_delete_action"]');

    /* ********************************************************************** */
    // Disable/Enable.
    /* ********************************************************************** */

    // Check bulk operation disable.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.webform_bulk_form', FALSE)
      ->save();
    $this->drupalGet('/admin/structure/webform');
    $this->assertNoCssSelect('#webform-bulk-form');

    // Re-enable bulk operations.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.webform_bulk_form', TRUE)
      ->save();

    /* ********************************************************************** */
    // Open/Close.
    /* ********************************************************************** */

    // Check webform one is opened.
    $this->assertTrue($webforms[0]->isOpen());

    // Check webform close action.
    $this->drupalGet('/admin/structure/webform');
    $edit = ['action' => 'webform_close_action', 'items[one]' => TRUE];
    $this->submitForm($edit, 'Apply to selected items', 'webform-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Close webform</em> was applied to 1 item.');
    $this->assertCssSelect('#edit-items-one');
    $this->assertCssSelect('#edit-items-two');
    $this->assertCssSelect('#edit-items-three');

    // Check webform one is now closed.
    $webforms[0] = $this->reloadWebform('one');
    $this->assertTrue($webforms[0]->isClosed());

    // Check webform close action.
    $this->drupalGet('/admin/structure/webform');
    $edit = [
      'action' => 'webform_open_action',
      'items[one]' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Open webform</em> was applied to 1 item.');

    // Check webform one is now open.
    $webforms[0] = $this->reloadWebform('one');
    $this->assertTrue($webforms[0]->isOpen());

    /* ********************************************************************** */
    // Archive/Restore.
    /* ********************************************************************** */

    // Check webform archive action.
    $this->drupalGet('/admin/structure/webform');
    $edit = [
      'action' => 'webform_archive_action',
      'items[one]' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Archive webform</em> was applied to 1 item.');
    $this->assertNoCssSelect('#edit-items-one');

    // Check webform one is now archived.
    $webforms[0] = $this->reloadWebform('one');
    $this->assertTrue($webforms[0]->isArchived());
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $this->assertCssSelect('#edit-items-one');

    // Check webform unarchive action.
    $options = ['query' => ['state' => 'archived']];
    $this->drupalGet('/admin/structure/webform', $options);
    $edit = ['action' => 'webform_unarchive_action', 'items[one]' => TRUE];
    $this->submitForm($edit, 'Apply to selected items', 'webform-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Restore webform</em> was applied to 1 item.');

    // Check webform one is now archived.
    $webforms[0] = $this->reloadWebform('one');
    $this->assertFalse($webforms[0]->isArchived());

    /* ********************************************************************** */
    // Delete.
    /* ********************************************************************** */

    // Check webform delete action.
    $this->drupalGet('/admin/structure/webform');
    $edit = ['action' => 'webform_delete_action', 'items[one]' => TRUE];
    $this->submitForm($edit, 'Apply to selected items', 'webform-bulk-form');
    $edit = ['confirm_input' => TRUE];
    $this->submitForm($edit, 'Delete');
    $assert_session->responseContains('Deleted 1 item.');

    // Check webform one is now deleted.
    $webforms[0] = $this->reloadWebform('one');
    $this->assertNull($webforms[0]);
  }

  /**
   * Tests the webform overview access.
   */
  public function testAccess() {
    $assert_session = $this->assertSession();

    // Test with a superuser.
    $any_webform_user = $this->createUser([
      'access webform overview',
      'create webform',
      'edit any webform',
      'delete any webform',
    ]);
    $this->drupalLogin($any_webform_user);
    $list_path = '/admin/structure/webform';
    $this->drupalGet($list_path);
    $assert_session->linkExists('Test: Submissions');
    $assert_session->linkExists('Results');
    $assert_session->linkExists('Build');
    $assert_session->linkExists('Settings');
    $assert_session->linkExists('View');
    $assert_session->linkExists('Duplicate');
    $assert_session->linkExists('Delete');

    // Test with a user that only has submission access.
    $any_webform_submission_user = $this->createUser([
      'access webform overview',
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]);
    $this->drupalLogin($any_webform_submission_user);
    $this->drupalGet($list_path);
    // Webform name should not be a link as the user doesn't have access to the
    // submission page.
    $assert_session->linkExists('Test: Submissions');
    $assert_session->linkExists('Results');
    $assert_session->linkNotExists('Build');
    $assert_session->linkNotExists('Settings');
    $assert_session->linkExists('View');
    $assert_session->linkNotExists('Duplicate');
    $assert_session->linkNotExists('Delete');

    // Disable webform page setting to ensure the view links get removed.
    $webform_config = \Drupal::configFactory()->getEditable('webform.webform.test_submissions');
    $settings = $webform_config->get('settings');
    $settings['page'] = FALSE;
    $webform_config->set('settings', $settings)->save();
    $this->drupalGet($list_path);
    $assert_session->linkNotExists('Test: Submissions');
    $assert_session->responseContains('Test: Submissions');
    $this->assertLinkNotInRow('Test: Submissions', 'View');

    // Test with role that is configured via webform access settings.
    $rid = $this->drupalCreateRole(['access webform overview']);
    $special_access_user = $this->createUser();
    $special_access_user->addRole($rid);
    $special_access_user->save();
    $access = $webform_config->get('access');
    $access['view_any']['roles'][] = $rid;
    $webform_config->set('access', $access)->save();
    $this->drupalLogin($special_access_user);
    $this->drupalGet($list_path);
    $assert_session->responseContains('Test: Submissions');
    $assert_session->linkExists('Results');
  }

  /**
   * Asserts a link is not in a row.
   *
   * @param string $row_text
   *   Text to find a row.
   * @param string $link
   *   The link to find.
   *
   * @throws \Exception
   *   When the row can't be found.
   */
  protected function assertLinkNotInRow($row_text, $link): void {
    $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $row_text));
    if (!$row) {
      throw new \Exception($this->getSession()->getDriver(), 'table row', 'value', $row_text);
    }

    $links = $row->findAll('named', ['link', $link]);
    $this->assertEmpty($links, sprintf('Link with label %s found in row %s.', $link, $row_text));
  }

}
