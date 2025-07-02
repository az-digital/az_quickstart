<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Settings;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests webform JavaScript.
 *
 * @group webform_javascript
 */
class WebformSettingsAjaxJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_ajax',
    'test_ajax_confirmation_inline',
    'test_ajax_confirmation_message',
    'test_ajax_confirmation_modal',
    'test_ajax_confirmation_page',
    'test_ajax_confirmation_url',
    'test_ajax_confirmation_url_msg',
  ];

  /**
   * Tests Ajax.
   */
  public function testAjax() {

    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Test Ajax. (test_ajax)
    /* ********************************************************************** */

    $webform_ajax = Webform::load('test_ajax');

    // Validate form.
    $this->drupalGet($webform_ajax->toUrl());
    $this->submitForm(['textfield' => ''], 'Submit');
    $assert_session->waitForElement('css', '.messages--error');

    // Check validation message.
    $assert_session->responseContains('textfield field is required.');

    // Preview form.
    $this->drupalGet($webform_ajax->toUrl());
    $this->submitForm(['textfield' => 'test value'], 'Preview');
    $assert_session->waitForElement('css', '.messages--warning');

    // Check preview message.
    $assert_session->responseContains('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    // Submit form.
    $this->drupalGet($webform_ajax->toUrl());
    $this->submitForm(['textfield' => 'test value'], 'Submit');
    $assert_session->waitForElement('css', '.messages--status');

    // Check submit message.
    $assert_session->responseContains('New submission added to Test: Ajax.');

    // Check that submission was created.
    $sid = $this->getLastSubmissionId($webform_ajax);
    $this->assertEquals($sid, 1);

    // Check that text field is blank.
    $assert_session->fieldValueEquals('textfield', '');

    /* ********************************************************************** */
    // Test Ajax confirmation inline. (test_ajax_confirmation_inline)
    /* ********************************************************************** */

    $webform_ajax_confirmation_inline = Webform::load('test_ajax_confirmation_inline');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_inline->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForElement('css', '.messages--status');
    $assert_session->waitForText('This is a custom inline confirmation message.');

    // Check submit message.
    $assert_session->responseContains('This is a custom inline confirmation message.');

    // Click back to form.
    $this->clickLink('Back to form');
    $assert_session->waitForButton('Submit');

    // Check submit message.
    $assert_session->responseNotContains('This is a custom inline confirmation message.');
    $assert_session->responseContains('This webform will display the confirmation inline when submitted.');

    /* ********************************************************************** */
    // Test Ajax confirmation message. (test_ajax_confirmation_message)
    /* ********************************************************************** */

    $webform_ajax_confirmation_message = Webform::load('test_ajax_confirmation_message');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_message->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForElement('css', '.messages--status');

    // Check confirmation message.
    $assert_session->responseContains('This is a <b>custom</b> confirmation message.');
    $assert_session->responseContains('This webform will display a confirmation message when submitted.');

    /* ********************************************************************** */
    // Test Ajax confirmation message. (test_ajax_confirmation_modal)
    /* ********************************************************************** */

    $webform_ajax_confirmation_modal = Webform::load('test_ajax_confirmation_modal');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_modal->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForElementVisible('css', '.ui-dialog.webform-confirmation-modal');

    // Check confirmation modal.
    $assert_session->responseContains('This is a <b>custom</b> confirmation modal.');

    /* ********************************************************************** */
    // Test Ajax confirmation page. (test_ajax_confirmation_page)
    /* ********************************************************************** */

    $webform_ajax_confirmation_page = Webform::load('test_ajax_confirmation_page');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_page->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForLink('Back to form');

    // Check confirmation page message.
    $assert_session->responseContains('This is a custom confirmation page.');

    /* ********************************************************************** */
    // Test Ajax confirmation url. (test_ajax_confirmation_url)
    /* ********************************************************************** */

    $webform_ajax_confirmation_url = Webform::load('test_ajax_confirmation_url');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_url->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForElement('css', '.path-front');

    // Check current page is <front>.
    $assert_session->addressEquals('/');

    /* ********************************************************************** */
    // Test Ajax confirmation url with message. (test_ajax_confirmation_url_msg)
    /* ********************************************************************** */

    $webform_ajax_confirmation_url_msg = Webform::load('test_ajax_confirmation_url_msg');

    // Submit form.
    $this->drupalGet($webform_ajax_confirmation_url_msg->toUrl());
    $this->submitForm([], 'Submit');
    $assert_session->waitForElement('css', '.path-front');

    // Check current page is <front>.
    $assert_session->addressEquals('/');

    // Check confirmation message.
    $assert_session->responseContains('This is a custom confirmation message.');
  }

}
