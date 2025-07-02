<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\file\Entity\File;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for remote post webform handler functionality.
 *
 * @group webform
 */
class WebformHandlerRemotePostTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['file', 'webform', 'webform_test_handler_remote_post'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_handler_remote_post',
    'test_handler_remote_put',
    'test_handler_remote_get',
    'test_handler_remote_post_file',
    'test_handler_remote_post_cast',
  ];

  /**
   * Test remote post handler.
   */
  public function testRemotePostHandler() {
    global $base_url;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    // POST.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post');

    // Check 'completed' operation.
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);

    // Check POST response.
    $assert_session->responseContains("method: post
status: success
message: &#039;Processed completed request.&#039;
options:
  headers:
    Accept-Language: en
    custom_header: &#039;true&#039;
  form_params:
    custom_completed: true
    custom_data: true
    response_type: &#039;200&#039;
    first_name: John
    last_name: Smith");

    $assert_session->responseContains("form_params:
  custom_completed: true
  custom_data: true
  response_type: &#039;200&#039;
  first_name: John
  last_name: Smith");
    $assert_session->responseContains('This is a custom 200 success message.');

    // Check confirmation number is set via the
    // [webform:handler:remote_post:completed:confirmation_number] token.
    $assert_session->responseContains('Your confirmation number is ' . $webform_submission->getElementData('confirmation_number') . '.');

    // Check custom header.
    $assert_session->responseContains('{&quot;headers&quot;:{&quot;Accept-Language&quot;:&quot;en&quot;,&quot;custom_header&quot;:&quot;true&quot;}');

    // Sleep for 1 second to make sure submission timestamp is updated.
    sleep(1);

    // Check 'updated' operation.
    $this->drupalGet("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseContains("form_params:
  custom_updated: true
  custom_data: true
  response_type: &#039;200&#039;
  first_name: John
  last_name: Smith");
    $assert_session->responseContains('Processed updated request.');

    // Check 'deleted`' operation.
    $this->drupalGet("admin/structure/webform/manage/test_handler_remote_post/submission/$sid/delete");
    $this->submitForm([], 'Delete');
    $assert_session->responseContains("form_params:
  custom_deleted: true
  custom_data: true
  first_name: John
  last_name: Smith
  response_type: &#039;200&#039;");
    $assert_session->responseContains('Processed deleted request.');

    // Switch anonymous user.
    $this->drupalLogout();

    // Check 'draft' operation.
    $this->postSubmission($webform, [], 'Save Draft');
    $assert_session->responseContains("form_params:
  custom_draft_created: true
  custom_data: true
  response_type: &#039;200&#039;
  first_name: John
  last_name: Smith");
    $assert_session->responseContains('Processed draft_created request.');

    // Login root user.
    $this->drupalLogin($this->rootUser);

    // Check 'convert' operation.
    $assert_session->responseContains("form_params:
  custom_converted: true
  custom_data: true
  first_name: John
  last_name: Smith
  response_type: &#039;200&#039;");
    $assert_session->responseContains('Processed converted request.');
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');

    // Check excluded data.
    $webform->getHandler('remote_post')
      ->setSetting('excluded_data', [
        'last_name' => 'last_name',
      ]);
    $webform->save();
    $sid = $this->postSubmission($webform);
    $assert_session->responseContains('first_name: John');
    $assert_session->responseNotContains('last_name: Smith');
    $assert_session->responseContains("sid: &#039;$sid&#039;");
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');

    // Check 200 Success Error.
    $this->postSubmission($webform, ['response_type' => 200]);
    $assert_session->responseContains('This is a custom 200 success message.');
    $assert_session->responseContains('Processed completed request.');

    // Check 500 Internal Server Error.
    $this->postSubmission($webform, ['response_type' => '500']);
    $assert_session->responseNotContains('Processed completed request.');
    $assert_session->responseContains('Failed to process completed request.');
    $assert_session->responseContains('Unable to process this submission. Please contact the site administrator.');

    // Check default custom response message.
    $handler = $webform->getHandler('remote_post');
    $handler->setSetting('message', 'This is a custom response message');
    $webform->save();
    $this->postSubmission($webform, ['response_type' => '500']);
    $assert_session->responseContains('Failed to process completed request.');
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');
    $assert_session->responseContains('This is a custom response message');

    // Check 201 Completed with no custom message.
    $this->postSubmission($webform, ['response_type' => '201']);

    $assert_session->responseNotContains('Processed created request.');
    $assert_session->responseNotContains('This is a custom 404 not found message.');

    // Check 404 Not Found with custom message.
    $this->postSubmission($webform, ['response_type' => '404']);
    $assert_session->responseContains('File not found');
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');
    $assert_session->responseContains('This is a custom 404 not found message.');

    // Check 401 Unauthorized with custom message and token.
    $this->postSubmission($webform, ['response_type' => '401']);
    $assert_session->responseContains('Unauthorized');
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');
    $assert_session->responseContains('This is a message token <strong>Unauthorized to process completed request.</strong>');

    // Check 405 Method Not Allowed with custom message and token.
    $this->postSubmission($webform, ['response_type' => '405']);
    $assert_session->responseContains('Method Not Allowed');
    $assert_session->responseNotContains('Unable to process this submission. Please contact the site administrator.');
    $assert_session->responseContains('This is a array token <strong>[webform:handler:remote_post:options]</strong>');

    // Disable saving of results.
    $webform->setSetting('results_disabled', TRUE);
    $webform->save();

    // Check confirmation number when results disabled.
    $sid = $this->postSubmission($webform);
    $this->assertNull($sid);

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-Z0-9]+)&quot;/', $this->getSession()->getPage()->getContent(), $match);
    $assert_session->responseContains('Your confirmation number is ' . $match[1] . '.');

    // Set remote post error URL to homepage.
    $handler = $webform->getHandler('remote_post');
    $handler->setSetting('error_url', $webform->toUrl('canonical', ['query' => ['error' => '1']])->toString());
    $webform->save();

    // Check 404 Not Found with custom error uri.
    $this->postSubmission($webform, ['response_type' => '404']);
    $assert_session->responseContains('This is a custom 404 not found message.');
    $assert_session->addressEquals($webform->toUrl('canonical', ['query' => ['error' => '1']])->setAbsolute()->toString());

    /* ********************************************************************** */
    // PUT.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_put');

    $this->postSubmission($webform);

    // Check PUT response.
    $assert_session->responseContains("method: put
status: success
message: &#039;Processed completed request.&#039;
options:
  headers:
    custom_header: &#039;true&#039;
  form_params:
    custom_completed: true
    custom_data: true
    response_type: &#039;200&#039;
    first_name: John
    last_name: Smith");

    /* ********************************************************************** */
    // GET.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_get');

    $this->postSubmission($webform);

    // Check GET response.
    $assert_session->responseContains("method: get
status: success
message: &#039;Processed completed request.&#039;
options:
  headers:
    custom_header: &#039;true&#039;");

    // Check request URL contains query string.
    $assert_session->responseContains("http://webform-test-handler-remote-post/completed?custom_completed=1&amp;custom_data=1&amp;response_type=200&amp;first_name=John&amp;last_name=Smith");

    // Check response data.
    $assert_session->responseContains("message: &#039;Processed completed request.&#039;");

    // Get confirmation number from JSON packet.
    preg_match('/&quot;confirmation_number&quot;:&quot;([a-zA-Z0-9]+)&quot;/', $this->getSession()->getPage()->getContent(), $match);
    $assert_session->responseContains('Your confirmation number is ' . $match[1] . '.');

    /* ********************************************************************** */
    // POST File.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post_file');

    $sid = $this->postSubmissionTest($webform);
    $webform_submission = WebformSubmission::load($sid);

    $file_data = $webform_submission->getElementData('file');
    $file = File::load($file_data);
    $file_id = $file->id();
    $file_uuid = $file->uuid();

    $files_data = $webform_submission->getElementData('files');
    $file = File::load(reset($files_data));
    $files_id = $file->id();
    $files_uuid = $file->uuid();

    // Check the file name, uri, and data is appended to form params.
    $assert_session->responseContains("form_params:
  file: 1
  files:
    - 2
  _file:
    id: $file_id
    name: file.txt
    uri: &#039;private://webform/test_handler_remote_post_file/$sid/file.txt&#039;
    mime: text/plain
    uuid: $file_uuid
    data: dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==
  _files:
    -
      id: $files_id
      name: files.txt
      uri: &#039;private://webform/test_handler_remote_post_file/$sid/files.txt&#039;
      mime: text/plain
      uuid: $files_uuid
      data: dGhpcyBpcyBhIHNhbXBsZSB0eHQgZmlsZQppdCBoYXMgdHdvIGxpbmVzCg==");

    // Check the file data is NOT appended to form params.
    $handler = $webform->getHandler('remote_post');
    $handler->setSetting('file_data', FALSE);
    $webform->save();
    $this->drupalGet("/admin/structure/webform/manage/test_handler_remote_post_file/submission/$sid/edit");
    $this->submitForm([], 'Save');
    $assert_session->responseContains("form_params:
  file: 1
  files:
    - 2
  _file:
    id: $file_id
    name: file.txt
    uri: &#039;private://webform/test_handler_remote_post_file/$sid/file.txt&#039;
    mime: text/plain
    uuid: $file_uuid
  _files:
    -
      id: $files_id
      name: files.txt
      uri: &#039;private://webform/test_handler_remote_post_file/$sid/files.txt&#039;
      mime: text/plain
      uuid: $files_uuid");

    /* ********************************************************************** */
    // POST cast.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post_cast');

    $this->postSubmission($webform);

    // @todo Remove once Drupal 10.0.x is only supported.
    if (floatval(\Drupal::VERSION) >= 10) {
      $assert_session->responseContains("form_params:
  boolean_true: true
  integer: 100
  float: 100.01
  checkbox: false
  number: &#039;&#039;
  number_multiple: {  }
  custom_composite:
    -
      textfield: &#039;&#039;
      number: 0.0
      checkbox: false");
    }
    else {
      $assert_session->responseContains("form_params:
  boolean_true: true
  integer: 100
  float: 100.01
  checkbox: false
  number: &#039;&#039;
  number_multiple: {  }
  custom_composite:
    -
      textfield: &#039;&#039;
      number: !!float 0
      checkbox: false");
    }

    $edit = [
      'checkbox' => TRUE,
      'number' => '10',
      'number_multiple[items][0][_item_]' => '10.5',
      'custom_composite[items][0][textfield]' => 'text',
      'custom_composite[items][0][checkbox]' => TRUE,
      'custom_composite[items][0][number]' => '20.5',
    ];
    $this->postSubmission($webform, $edit);
    // @todo Remove once Drupal 10.0.x is only supported.
    if (floatval(\Drupal::VERSION) >= 10) {
      $assert_session->responseContains("form_params:
  boolean_true: true
  integer: 100
  float: 100.01
  checkbox: true
  number: 10.0
  number_multiple:
    - 10.5
  custom_composite:
    -
      textfield: text
      checkbox: true
      number: 20.5");
    }
    else {
      $assert_session->responseContains("form_params:
  boolean_true: true
  integer: 100
  float: 100.01
  checkbox: true
  number: !!float 10
  number_multiple:
    - 10.5
  custom_composite:
    -
      textfield: text
      checkbox: true
      number: 20.5");
    }

    /* ********************************************************************** */
    // POST error.
    /* ********************************************************************** */

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_remote_post_error');

    $this->postSubmission($webform);

    $this->assertEquals($base_url . '/error_url', $this->getUrl());
  }

}
