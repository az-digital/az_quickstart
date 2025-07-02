<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission form unique limit.
 *
 * @group webform
 */
class WebformSettingsLimitUniqueTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_limit_total_unique',
    'test_form_limit_user_unique',
  ];

  /**
   * Tests webform submission form unique limit.
   */
  public function testLimitUnique() {
    $assert_session = $this->assertSession();

    $webform_total_unique = Webform::load('test_form_limit_total_unique');
    $webform_user_unique = Webform::load('test_form_limit_user_unique');

    $user = $this->drupalCreateUser();
    $admin_user = $this->drupalCreateUser(['administer webform']);
    $manage_any_user = $this->drupalCreateUser(['view any webform submission', 'edit any webform submission']);
    $edit_any_user = $this->drupalCreateUser(['edit any webform submission']);
    $manage_own_user = $this->drupalCreateUser(['view own webform submission', 'edit own webform submission']);
    $edit_user_only = $this->drupalCreateUser(['edit own webform submission']);

    /* ********************************************************************** */
    // Total unique. (webform)
    /* ********************************************************************** */

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->statusCodeEquals(403);
    $assert_session->fieldNotExists('name');

    // Check that access is allowed for edit any submission user.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that 'Test' form is available and display a message.
    $this->drupalGet('/webform/test_form_limit_total_unique/test');
    $assert_session->responseContains(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that name is empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for admin user.
    $this->drupalLogin($admin_user);
    $sid = $this->postSubmission($webform_total_unique, ['name' => 'John Smith']);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for root user.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Smith'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_total_unique/test');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");
    $assert_session->responseNotContains(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that edit any submission user can access and edit.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    /* ********************************************************************** */
    // Total unique. (node)
    /* ********************************************************************** */

    $this->drupalLogout();

    // Create webform node.
    $node_total_unique = $this->createWebformNode('test_form_limit_total_unique');

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->statusCodeEquals(403);
    $assert_session->fieldNotExists('name');
    $this->drupalGet('/node/' . $node_total_unique->id());
    $assert_session->statusCodeEquals(403);

    // Check that access is denied for authenticated user.
    $this->drupalLogin($user);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->statusCodeEquals(403);
    $assert_session->fieldNotExists('name');
    $this->drupalGet('/node/' . $node_total_unique->id());
    $assert_session->statusCodeEquals(403);

    // Check that access is allowed for edit any submission user.
    $this->drupalLogin($manage_any_user);
    $this->drupalGet('/node/' . $node_total_unique->id());
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/node/' . $node_total_unique->id());
    $assert_session->fieldValueEquals('name', '');

    // Check that name is set to 'John Lennon' and 'Submission information' is
    // visible for admin user.
    $sid = $this->postNodeSubmission($node_total_unique, ['name' => 'John Lennon']);
    $this->drupalGet('/webform/test_form_limit_total_unique');
    $assert_session->fieldValueEquals('name', 'John Lennon');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Lennon'
    // and does not display a message.
    $this->drupalGet('/node/' . $node_total_unique->id() . '/webform/test');
    $assert_session->fieldValueEquals('name', 'John Lennon');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Lennon'
    // and does not display a message for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/node/' . $node_total_unique->id() . '/webform/test');
    $assert_session->fieldValueEquals('name', 'John Lennon');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    /* ********************************************************************** */
    // User unique. (webform)
    /* ********************************************************************** */

    $this->drupalLogout();

    // Check that access is denied for anonymous users.
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->statusCodeEquals(403);

    // Check that access is denied for authenticated user.
    $this->drupalLogin($user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->statusCodeEquals(403);

    // Check that access is denied for edit any user.
    $this->drupalLogin($edit_any_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->statusCodeEquals(403);

    // Check that access is denied for edit own user.
    $this->drupalLogin($edit_user_only);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->statusCodeEquals(403);

    // Check that access is allowed for edit own submission user.
    $this->drupalLogin($manage_own_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that name is empty for new submission for admin user.
    $this->drupalLogin($admin_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that 'Test' form is available and display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $assert_session->responseContains(' The below webform has been prepopulated with custom/random test data. When submitted, this information <strong>will still be saved</strong> and/or <strong>sent to designated recipients</strong>');

    // Check that name is empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for admin user.
    $this->drupalLogin($admin_user);
    $sid = $this->postSubmission($webform_user_unique, ['name' => 'John Smith']);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that 'Test' form also has name set to 'John Smith'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that access is allowed for edit own submission user.
    $this->drupalLogin($manage_own_user);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', '');

    /* ********************************************************************** */

    // Check that name is still empty for new submission for root user.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', '');

    // Check that name is set to 'John Smith' and 'Submission information' is
    // visible for root user.
    $sid = $this->postSubmission($webform_user_unique, ['name' => 'Jane Doe']);
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldValueEquals('name', 'Jane Doe');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that the delete submission link includes ?destination.
    $assert_session->linkByHrefExists(base_path() . 'webform/test_form_limit_user_unique/submissions/' . $sid . '/delete?destination=' . base_path() . 'form/test-form-limit-user-unique');

    // Check that 'Test' form also has name set to 'Jane Doe'
    // and does not display a message.
    $this->drupalGet('/webform/test_form_limit_user_unique/test');
    $assert_session->fieldValueEquals('name', 'Jane Doe');
    $assert_session->responseContains("<div><b>Submission ID:</b> $sid</div>");

    // Check that the delete submission link does not include the ?destination.
    $assert_session->linkByHrefExists(base_path() . 'admin/structure/webform/manage/test_form_limit_user_unique/submission/' . $sid . '/delete');

    /* ********************************************************************** */

    // Check that access is allowed for manage own submission user.
    $this->drupalLogin($manage_own_user);
    $this->postSubmission($webform_user_unique, ['name' => 'John Adams']);
    $assert_session->fieldValueEquals('name', 'John Adams');

    // Check that manage own submission user can't update a closed webform.
    $webform_user_unique->setStatus(FALSE)->save();
    $this->drupalGet('/webform/test_form_limit_user_unique');
    $assert_session->fieldNotExists('name');
    $assert_session->responseContains('Sorry… This form is closed to new submissions.');
  }

}
