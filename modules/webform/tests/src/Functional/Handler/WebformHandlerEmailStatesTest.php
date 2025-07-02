<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for email webform handler email states.
 *
 * @group webform
 */
class WebformHandlerEmailStatesTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_states'];

  /**
   * Test email states handler.
   */
  public function testEmailStates() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_email_states');

    // Check draft saved email.
    $this->drupalGet('/webform/test_handler_email_states');
    $this->submitForm([], 'Save Draft');
    $assert_session->responseContains('Debug: Email: Draft saved');

    // Check completed email.
    $sid = $this->postSubmission($webform);
    $assert_session->responseContains('Debug: Email: Submission completed');

    $this->drupalLogin($this->rootUser);

    // Check converted email.
    $email = $this->getLastEmail();
    $this->assertEquals($email['id'], 'webform_test_handler_email_states_email_converted');

    // Check updated email.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/edit");
    $this->submitForm([], 'Save');

    /* ********************************************************************** */
    // @todo Fix random test failure that can't be reproduced locally.
    // $assert_session->responseContains('Debug: Email: Submission updated');
    /* ********************************************************************** */

    // Check that custom (aka no states) is only visible on the 'Resend' tab.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/resend");
    $assert_session->responseContains('<b>Subject:</b> Draft saved<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission converted<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission completed<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission updated<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission locked<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission deleted<br />');
    $assert_session->responseContains('<b>Subject:</b> Submission custom<br />');

    // Check locked email.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/notes");
    $this->submitForm(['locked' => TRUE], 'Save');
    $assert_session->responseContains('Debug: Email: Submission locked');

    // Check deleted email.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/delete");
    $this->submitForm([], 'Delete');
    $assert_session->responseContains('Debug: Email: Submission deleted');

    // Check that 'Send when…' is visible.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $assert_session->responseContains('<span class="fieldset-legend">Send email</span>');

    // Check states hidden when results are disabled.
    $webform->setSetting('results_disabled', TRUE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $assert_session->responseNotContains('<span class="fieldset-legend js-form-required form-required">Send email</span>');

    // Check that only completed email is triggered when states are disabled.
    $this->postSubmission($webform);
    $assert_session->responseNotContains('Debug: Email: Draft saved');
    $assert_session->responseContains('Debug: Email: Submission completed');
    $assert_session->responseNotContains('Debug: Email: Submission updated');
    $assert_session->responseNotContains('Debug: Email: Submission deleted');
    $assert_session->responseNotContains('Debug: Email: Submission custom');

    // Check that resave draft handler automatically switches
    // states to completed.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $this->submitForm([], 'Save');
    $this->postSubmission($webform);
    $assert_session->responseContains('Debug: Email: Draft saved');
    $assert_session->responseContains('Debug: Email: Submission completed');
    $assert_session->responseNotContains('Debug: Email: Submission updated');
    $assert_session->responseNotContains('Debug: Email: Submission deleted');
    $assert_session->responseNotContains('Debug: Email: Submission custom');
  }

}
