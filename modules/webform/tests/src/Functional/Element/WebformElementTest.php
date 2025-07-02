<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform element.
 *
 * @group webform
 */
class WebformElementTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_element'];

  /**
   * Tests webform element.
   */
  public function testWebform() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('contact');

    // Check webform render.
    $this->drupalGet('/webform_test_element');
    $assert_session->fieldValueEquals('email', '');
    $assert_session->fieldValueEquals('name', '');
    $assert_session->fieldValueEquals('subject', '');
    $assert_session->fieldValueEquals('message', '');

    // Check webform lazy render.
    $this->drupalGet('/webform_test_element', ['query' => ['lazy' => TRUE]]);
    $assert_session->fieldValueEquals('email', '');
    $assert_session->fieldValueEquals('name', '');
    $assert_session->fieldValueEquals('subject', '');
    $assert_session->fieldValueEquals('message', '');

    // Check webform default data.
    $this->drupalGet('/webform_test_element', ['query' => ['default_data' => 'email: test']]);
    $assert_session->fieldValueEquals('email', 'test');

    // Check webform action.
    $this->drupalGet('/webform_test_element', ['query' => ['action' => 'http://drupal.org']]);
    $assert_session->responseContains('action="http://drupal.org"');

    // Check webform submit.
    $this->drupalGet('/webform_test_element');
    $edit = [
      'email' => 'example@example.com',
      'name' => '{name}',
      'subject' => '{subject}',
      'message' => '{message}',
    ];
    $this->submitForm($edit, 'Send message');
    $assert_session->addressEquals('/');
    $assert_session->responseContains('Your message has been sent.');

    // Get last submission id.
    $sid = $this->getLastSubmissionId($webform);

    // Check submission is not render.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $assert_session->fieldNotExists('email');

    // Set webform access denied to display a message, instead of nothing.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_MESSAGE);
    $webform->save();

    // Check submission access denied message is displayed.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $assert_session->responseContains("Please log in to access this form.");

    // Login as root.
    $this->drupalLogin($this->rootUser);

    // Check submission can be edited.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid]]);
    $assert_session->fieldValueEquals('email', 'example@example.com');
    $assert_session->fieldValueEquals('name', '{name}');
    $assert_session->fieldValueEquals('subject', '{subject}');
    $assert_session->fieldValueEquals('message', '{message}');
    $assert_session->responseContains('Submission information');

    // Check submission information is hidden.
    $this->drupalGet('/webform_test_element', ['query' => ['sid' => $sid, 'information' => 'false']]);
    $assert_session->responseNotContains('Submission information');
  }

}
