<?php

namespace Drupal\Tests\webform_attachment\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform_attachment\Element\WebformAttachmentToken;

/**
 * Tests for webform example element.
 *
 * @group webform_attachment
 */
class WebformAttachmentTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['token', 'webform_attachment', 'webform_attachment_test'];

  /**
   * Tests webform attachment.
   */
  public function testWebformAttachment() {
    global $base_url;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // Email.
    /* ********************************************************************** */

    $webform_id = 'test_attachment_email';
    $webform_attachment_email = Webform::load($webform_id);
    $attachment_date = date('Y-m-d');

    // Check that the attachment is added to the sent email.
    $sid = $this->postSubmission($webform_attachment_email);
    $sent_email = $this->getLastEmail();
    $this->assertEquals($sent_email['params']['attachments'][0]['filename'], "attachment_token-$attachment_date.xml", "The attachment's file name");
    $this->assertEquals($sent_email['params']['attachments'][0]['filemime'], 'application/xml', "The attachment's file mime type");
    $this->assertEquals($sent_email['params']['attachments'][0]['filecontent'], "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<asx:abap xmlns:asx=\"http://www.sap.com/abapxml\" version=\"1.0\">
   <asx:values>
      <VERSION>1.0</VERSION>
      <SENDER>johnsmith@example.com</SENDER>
      <WEBFORM_ID>test_attachment_email</WEBFORM_ID>
      <SOURCE>
         <o2PARAVALU>
            <NAME>Lastname</NAME>
            <VALUE>Smith</VALUE>
         </o2PARAVALU>
         <o2PARAVALU>
            <NAME>Firstname</NAME>
            <VALUE>John</VALUE>
         </o2PARAVALU>
         <o2PARAVALU>
            <NAME>Emailaddress</NAME>
            <VALUE>johnsmith@example.com</VALUE>
         </o2PARAVALU>
      </SOURCE>
   </asx:values>
</asx:abap>", "The attachment's file content");

    // Check access to the attachment.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/attachment_token/attachment_token-$attachment_date.xml");
    $assert_session->statusCodeEquals(200);

    // Check access allowed to the attachment with any file name.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/attachment_token/any-file-name.text");
    $assert_session->statusCodeEquals(200);

    // Check page not found to not a webform.
    $this->drupalGet("/webform/not_a_webform/submissions/$sid/attachment/attachment/any-file-name.text");
    $assert_session->statusCodeEquals(404);

    // Check page not found when not an attachment element is specified.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/email/attachment-$attachment_date.xml");
    $assert_session->statusCodeEquals(404);

    /* ********************************************************************** */
    // Token.
    /* ********************************************************************** */

    $webform_id = 'test_attachment_token';
    $webform_attachment_token = Webform::load('test_attachment_token');

    $sid = $this->postSubmissionTest($webform_attachment_token, ['textfield' => 'Some text']);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_token/results/submissions');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token/test_token.txt">test_token.txt</a></td>');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token_download/test_token.txt">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_token/submission/$sid");
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token/test_token.txt">test_token.txt</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_token_download/test_token.txt">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_token_download/test_token.txt");
    $assert_session->responseContains('textfield: Some text');

    /* ********************************************************************** */
    // Twig.
    /* ********************************************************************** */

    $webform_id = 'test_attachment_twig';
    $webform_attachment_twig = Webform::load('test_attachment_twig');

    $sid = $this->postSubmissionTest($webform_attachment_twig, ['textfield' => 'Some text']);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_twig/results/submissions');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig/test_twig.xml">test_twig.xml</a></td>');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig_download/test_twig.xml">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_twig/submission/$sid");
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig/test_twig.txt">test_twig.xml</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_twig_download/test_twig.xml">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_twig_download/test_twig.xml");
    $assert_session->responseContains('<?xml version="1.0"?>
<textfield>Some text</textfield>');

    /* ********************************************************************** */
    // URL.
    /* ********************************************************************** */

    $webform_id = 'test_attachment_url';
    $webform_attachment_url = Webform::load('test_attachment_url');

    $sid = $this->postSubmissionTest($webform_attachment_url);

    // Check that both attachments are displayed on the results page.
    $this->drupalGet('/admin/structure/webform/manage/test_attachment_url/results/submissions');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a></td>');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_path/durpalicon.png">durpalicon.png</a></td>');
    $assert_session->responseContains('<td><a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a></td>');

    // Check that only the download attachment is displayed on
    // the submission page.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a>');
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_path/durpalicon.png">durpalicon.png</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a>');

    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url/MAINTAINERS.txt">MAINTAINERS.txt</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/webform_attachment_url_download/MAINTAINERS.txt">Download</a>');

    // Check the attachment's content.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/webform_attachment_url_download/MAINTAINERS.txt");
    $assert_session->responseContains('https://www.drupal.org/contribute');

    /* ********************************************************************** */
    // Access.
    /* ********************************************************************** */

    // Switch to anonymous user.
    $this->drupalLogout();

    $webform_id = 'test_attachment_access';
    $webform_attachment_access = Webform::load('test_attachment_access');
    $sid = $this->postSubmission($webform_attachment_access);
    $webform_submission = WebformSubmission::load($sid);

    // Check access to anonymous attachment allowed via $element access rules.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    // Check access allowed to anonymous.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    // Check access denied to authenticated.txt.
    $assert_session->statusCodeEquals(403);
    // Check access denied to private.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    $assert_session->statusCodeEquals(403);

    // Switch to authenticated user and set user as the submission's owner.
    $account = $this->createUser();
    $webform_submission->setOwnerId($account->id())->save();
    $this->drupalLogin($account);

    // Check access to authenticated attachment allowed via $element access rules.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    // Check access denied to anonymous.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $assert_session->statusCodeEquals(403);
    // Check access allow to authenticated.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    // Check access denied to private.txt.
    $assert_session->statusCodeEquals(403);

    // Switch to admin user.
    $this->drupalLogin($this->rootUser);

    // Check access to all attachment allowed for admin.
    $this->drupalGet("/admin/structure/webform/manage/test_attachment_url/submission/$sid");
    $assert_session->responseNotContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/anonymous/anonymous.txt">anonymous.txt</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/authenticated/authenticated.txt">authenticated.txt</a>');
    $assert_session->responseContains('<a href="' . $base_url . '/webform/' . $webform_id . '/submissions/' . $sid . '/attachment/private/private.txt">private.txt</a>');

    // Check access denied to anonymous.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/anonymous/anonymous.txt");
    $assert_session->statusCodeEquals(403);
    // Check access allowed to authenticated.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/authenticated/authenticated.txt");
    $assert_session->statusCodeEquals(200);
    // Check access allowed to private.txt.
    $this->drupalGet("/webform/$webform_id/submissions/$sid/attachment/private/private.txt");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Sanitize.
    /* ********************************************************************** */

    $webform_attachment_sanitize = Webform::load('test_attachment_sanitize');

    $sid = $this->postSubmissionTest($webform_attachment_sanitize, ['textfield' => 'Some text!@#$%^&*)']);
    $webform_submission = WebformSubmission::load($sid);
    $element = $webform_attachment_sanitize->getElement('webform_attachment_token');
    $this->assertEquals(WebformAttachmentToken::getFileName($element, $webform_submission), 'some-text.txt');

    /* ********************************************************************** */
    // States (enabled/disabled).
    /* ********************************************************************** */

    $webform_id = 'test_attachment_states';
    $webform_attachment_states = Webform::load($webform_id);

    // Check that attachment is enabled.
    $this->postSubmission($webform_attachment_states, ['attach' => TRUE]);
    $sent_email = $this->getLastEmail();
    $this->assertTrue(isset($sent_email['params']['attachments'][0]), 'Attachment enabled via #states');

    // Check that attachment is disabled.
    $this->postSubmission($webform_attachment_states, ['attach' => FALSE]);
    $sent_email = $this->getLastEmail();
    $this->assertFalse(isset($sent_email['params']['attachments'][0]), 'Attachment disabled via #states');
  }

}
