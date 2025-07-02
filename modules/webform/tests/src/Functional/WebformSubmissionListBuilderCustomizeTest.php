<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission list builder.
 *
 * @group webform
 */
class WebformSubmissionListBuilderCustomizeTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests customize.
   */
  public function testCustomize() {
    global $base_path;

    $assert_session = $this->assertSession();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
    ]);

    $own_submission_user = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');

    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');

    /* ********************************************************************** */
    // Customize default table.
    /* ********************************************************************** */

    // Check that access is denied to custom results default table.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $assert_session->statusCodeEquals(403);

    // Check that access is denied to custom results user table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $assert_session->statusCodeEquals(403);

    // Check that access is allowed to custom results default table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $assert_session->statusCodeEquals(200);

    // Check that access is denied to custom results user table.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $assert_session->statusCodeEquals(403);

    // Check that created is visible and changed is hidden.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseContains('sort by Created');
    $assert_session->responseNotContains('sort by Changed');

    // Check that first name is before last name.
    $assert_session->responseMatches('#First name.+Last name#s');

    // Check that no pager is being displayed.
    $assert_session->responseNotContains('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Check that table is sorted by created.
    $assert_session->responseContains('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');

    // Check the table results order by sid.
    $assert_session->responseMatches('#Hillary.+Abraham.+George#ms');

    // Check the table links to canonical view.
    $assert_session->responseContains('data-webform-href="' . $submissions[0]->toUrl()->toString() . '"');
    $assert_session->responseContains('data-webform-href="' . $submissions[1]->toUrl()->toString() . '"');
    $assert_session->responseContains('data-webform-href="' . $submissions[2]->toUrl()->toString() . '"');

    // Check webform state.
    $actual_state = \Drupal::state()->get('webform.webform.test_submissions');
    $this->assertNull($actual_state);

    // Customize to results default table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $edit = [
      'columns[created][checkbox]' => FALSE,
      'columns[changed][checkbox]' => TRUE,
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
      'sort' => 'element__first_name',
      'direction' => 'desc',
      'limit' => 20,
      'link_type' => 'table',
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The customized table has been saved.');

    // Check webform state.
    $actual_state = \Drupal::state()->get('webform.webform.test_submissions');
    $expected_state = [
      'results.custom.columns' => [
        0 => 'serial',
        1 => 'sid',
        2 => 'label',
        3 => 'uuid',
        4 => 'in_draft',
        5 => 'sticky',
        6 => 'locked',
        7 => 'notes',
        8 => 'element__last_name',
        9 => 'element__first_name',
        10 => 'completed',
        11 => 'changed',
        12 => 'entity',
        13 => 'uid',
        14 => 'remote_addr',
        15 => 'element__sex',
        16 => 'element__dob',
        17 => 'element__node',
        18 => 'element__colors',
        19 => 'element__likert',
        20 => 'element__likert__q1',
        21 => 'element__likert__q2',
        22 => 'element__likert__q3',
        23 => 'element__address',
        24 => 'element__address__address',
        25 => 'element__address__address_2',
        26 => 'element__address__city',
        27 => 'element__address__state_province',
        28 => 'element__address__postal_code',
        29 => 'element__address__country',
        30 => 'operations',
      ],
      'results.custom.sort' => 'element__first_name',
      'results.custom.direction' => 'desc',
      'results.custom.limit' => 20,
      'results.custom.link_type' => 'table',
      'results.custom.format' => [
        'header_format' => 'label',
        'element_format' => 'value',
      ],
      'results.custom.default' => TRUE,
    ];
    $this->assertEquals($expected_state, $actual_state);

    // Check that table now link to table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseContains('data-webform-href="' . $submissions[0]->toUrl('table')->toString() . '"');
    $assert_session->responseContains('data-webform-href="' . $submissions[1]->toUrl('table')->toString() . '"');
    $assert_session->responseContains('data-webform-href="' . $submissions[2]->toUrl('table')->toString() . '"');

    // Check that sid is hidden and changed is visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseNotContains('sort by Created');
    $assert_session->responseContains('sort by Changed');

    // Check that first name is now after last name.
    $assert_session->responseMatches('#Last name.+First name#ms');

    // Check the table results order by first name.
    $assert_session->responseMatches('#Hillary.+George.+Abraham#ms');

    // Manually set the limit to 1.
    $webform->setState('results.custom.limit', 1);

    // Check that only one result (Hillary #2) is displayed with pager.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseNotContains('George');
    $assert_session->responseNotContains('Abraham');
    $assert_session->responseNotContains('Hillary');
    $assert_session->responseContains('quotes&#039; &quot;');
    $assert_session->responseContains('<nav class="pager" role="navigation" aria-labelledby="pagination-heading">');

    // Reset the limit to 20.
    $webform->setState('results.custom.limit', 20);

    // Check Header label and element value display.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');

    // Check user header and value.
    $this->assertTableHeaderSort('User');
    $assert_session->responseContains('<td class="priority-medium">Anonymous</td>');

    // Check date of birth.
    $this->assertTableHeaderSort('Date of birth');
    $assert_session->responseContains('<td>Sunday, October 26, 1947</td>');

    // Display Header key and element raw.
    $webform->setState('results.custom.format', [
      'header_format' => 'key',
      'element_format' => 'raw',
    ]);

    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');

    // Check user header and value.
    $this->assertTableHeaderSort('uid');
    $assert_session->responseContains('<td class="priority-medium">0</td>');

    // Check date of birth.
    $this->assertTableHeaderSort('dob');
    $assert_session->responseContains('<td>1947-10-26</td>');

    /* ********************************************************************** */
    // Customize user results table.
    /* ********************************************************************** */

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Clear customized default able.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $this->submitForm($edit, 'Reset');
    $assert_session->responseContains('The customized table has been reset.');

    // Check that 'Customize' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseContains('>Customize<');
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Enabled customized results.
    $webform->setSetting('results_customize', TRUE)->save();

    // Check that 'Customize' button and link are not visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseNotContains('>Customize<');
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Check that 'Customize my table' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseContains('>Customize my table<');
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom/user");

    // Check that first name is before last name.
    $assert_session->responseMatches('#First name.+Last name#s');

    // Check that 'Customize default table' button and link are visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $assert_session->responseContains('>Customize default table<');
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/test_submissions/results/submissions/custom");

    // Switch to admin submission user.
    $this->drupalLogin($admin_submission_user);

    // Check that admin submission user is denied access to default table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $assert_session->statusCodeEquals(403);

    // Check that admin submission user is allowed access to user table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $assert_session->statusCodeEquals(200);

    // Customize to results user table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('Your customized table has been saved.');

    // Check that first name is now after last name.
    $assert_session->responseMatches('#Last name.+First name#ms');

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Customize to results default table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $edit = [
      'columns[element__first_name][checkbox]' => FALSE,
      'columns[element__last_name][checkbox]' => FALSE,
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The default customized table has been saved.');
    // Check that first name and last name are not visible.
    $assert_session->responseNotContains('First name');
    $assert_session->responseNotContains('Last name');

    // Switch to admin submission user.
    $this->drupalLogin($admin_submission_user);

    // Check that first name is still after last name.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseMatches('#Last name.+First name#ms');

    // Check that disabled customized results don't pull user data.
    $webform->setSetting('results_customize', FALSE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseNotContains('First name');
    $assert_session->responseNotContains('Last name');

    // Check that first name is still after last name.
    $webform->setSetting('results_customize', TRUE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseMatches('#Last name.+First name#ms');

    // Reset user customized table.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $this->submitForm($edit, 'Reset');
    $assert_session->responseContains('Your customized table has been reset.');

    // Check that first name and last name are now not visible.
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions');
    $assert_session->responseNotContains('First name');
    $assert_session->responseNotContains('Last name');

    /* ********************************************************************** */
    // Customize user results.
    /* ********************************************************************** */

    $this->drupalLogin($own_submission_user);

    // Check view own submissions.
    $this->drupalGet('/webform/test_submissions/submissions');
    $assert_session->responseContains('<th specifier="serial">');
    $assert_session->responseContains('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $assert_session->responseContains('<th specifier="remote_addr" class="priority-low">');

    // Display only first name and last name columns.
    $webform->setSetting('submission_user_columns', ['element__first_name', 'element__last_name'])
      ->save();

    // Check view own submissions only include first name and last name.
    $this->drupalGet('/webform/test_submissions/submissions');
    $assert_session->responseNotContains('<th specifier="serial">');
    $assert_session->responseNotContains('<th specifier="created" class="priority-medium is-active" aria-sort="descending">');
    $assert_session->responseNotContains('<th specifier="remote_addr" class="priority-low">');
    $assert_session->responseContains('<th specifier="element__first_name" aria-sort="ascending" class="is-active">');
    $assert_session->responseContains('<th specifier="element__last_name">');

    /* ********************************************************************** */
    // Webform delete.
    /* ********************************************************************** */

    // Switch to admin user.
    $this->drupalLogin($admin_user);

    // Set state and user data for the admin user.
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom');
    $this->submitForm($edit, 'Save');

    $this->drupalGet('/admin/structure/webform/manage/test_submissions/results/submissions/custom/user');
    $edit = [
      'columns[element__first_name][weight]' => '8',
      'columns[element__last_name][weight]' => '7',
    ];
    $this->submitForm($edit, 'Save');

    // Check that state and user data exists.
    $this->assertNotEmpty(\Drupal::state()->get('webform.webform.test_submissions'));
    $this->assertNotEmpty($user_data->get('webform', NULL, 'test_submissions'));

    // Delete the webform.
    $webform->delete();

    // Check that state and user data does not exist.
    $this->assertEmpty(\Drupal::state()->get('webform.webform.test_submissions'));
    $this->assertEmpty($user_data->get('webform', NULL, 'test_submissions'));
  }

  /**
   * Assert table header sorting.
   *
   * @param string $order
   *   Column table is sorted by.
   * @param string $sort
   *   Sort order for table column.
   * @param string|null $label
   *   Column label.
   */
  protected function assertTableHeaderSort($order, $sort = 'asc', $label = NULL): void {
    global $base_path;

    $assert_session = $this->assertSession();

    $label = $label ?: $order;

    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_submissions/results/submissions?sort=' . $sort . '&amp;order=' . str_replace(' ', '%20', $order) . '" title="sort by ' . $label . '" rel="nofollow">' . $label . '</a>');
  }

}
