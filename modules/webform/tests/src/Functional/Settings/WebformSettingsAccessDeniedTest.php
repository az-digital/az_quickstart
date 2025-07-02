<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Core\Url;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\WebformInterface;

/**
 * Tests for access denied webform and submissions.
 *
 * @group webform
 */
class WebformSettingsAccessDeniedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_access_denied'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place blocks.
    $this->placeBlocks();
  }

  /**
   * Tests webform access denied setting.
   */
  public function testWebformAccessDenied() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_form_access_denied');
    $webform_edit_route_url = Url::fromRoute('entity.webform.edit_form', [
      'webform' => $webform->id(),
    ]);

    /* ********************************************************************** */
    // Redirect.
    /* ********************************************************************** */

    // Set access denied to redirect with message.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_LOGIN);
    $webform->save();

    // Check form message is displayed and user is redirected to the login form.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $assert_session->responseContains('Please log in to access <b>Test: Webform: Access Denied</b>.');
    $route_options = [
      'query' => [
        'destination' => $webform_edit_route_url->toString(),
      ],
    ];
    $assert_session->addressEquals(Url::fromRoute('user.login', [], $route_options));

    /* ********************************************************************** */
    // Default.
    /* ********************************************************************** */

    // Set default access denied page.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check default access denied page.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $assert_session->responseContains('You are not authorized to access this page.');
    $assert_session->responseNotContains('Please log in to access <b>Test: Webform: Access Denied</b>.');

    /* ********************************************************************** */
    // Page.
    /* ********************************************************************** */

    // Set access denied to display a dedicated page.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_PAGE);
    $webform->setSetting('form_access_denied_title', 'Webform: Access denied');
    $webform->setSetting('form_access_denied_attributes', ['style' => 'border: 1px solid red', 'class' => [], 'attributes' => []]);
    $webform->save();

    // Check custom access denied page.
    $this->drupalGet('/admin/structure/webform/manage/test_form_access_denied');
    $assert_session->responseContains('<h1>Webform: Access denied</h1>');
    $assert_session->responseContains('<div style="border: 1px solid red" class="webform-access-denied">');
    $assert_session->responseContains('Please log in to access <b>Test: Webform: Access Denied</b>.');

    /* ********************************************************************** */
    // Message via a block.
    /* ********************************************************************** */

    // Place block.
    $this->drupalPlaceBlock('webform_block', [
      'webform_id' => 'test_form_access_denied',
    ]);

    // Set access denied to default.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check block does not displays access denied message.
    $this->drupalGet('<front>');
    $assert_session->responseNotContains('<div style="border: 1px solid red" class="webform-access-denied">');
    $assert_session->responseNotContains('Please log in to access <b>Test: Webform: Access Denied</b>.');

    // Set access denied to display a message.
    $webform->setSetting('form_access_denied', WebformInterface::ACCESS_DENIED_MESSAGE);
    $webform->save();

    // Check block displays access denied message.
    $this->drupalGet('<front>');
    $assert_session->responseContains('<div style="border: 1px solid red" class="webform-access-denied">');
    $assert_session->responseContains('Please log in to access <b>Test: Webform: Access Denied</b>.');

    // Login.
    $this->drupalLogin($this->rootUser);

    // Check block displays wth webform.
    $this->drupalGet('<front>');
    $assert_session->responseNotContains('<div class="webform-access-denied">');
    $assert_session->responseNotContains('Please log in to access <b>Test: Webform: Access Denied</b>.');
    $assert_session->responseContains('id="webform-submission-test-form-access-denied-user-1-add-form"');
  }

  /**
   * Tests webform submission access denied setting.
   */
  public function testWebformSubmissionAccessDenied() {
    $assert_session = $this->assertSession();

    // Create a webform submission.
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('test_form_access_denied');
    $sid = $this->postSubmission($webform);
    $this->drupalLogout();

    /* ********************************************************************** */
    // Redirect.
    /* ********************************************************************** */

    // Check submission message is displayed.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $assert_session->responseContains("Please log in to access <b>Test: Webform: Access Denied: Submission #$sid</b>.");

    /* ********************************************************************** */
    // Default.
    /* ********************************************************************** */

    // Set default access denied page.
    $webform->setSetting('submission_access_denied', WebformInterface::ACCESS_DENIED_DEFAULT);
    $webform->save();

    // Check default access denied page.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $assert_session->responseContains('You are not authorized to access this page.');
    $assert_session->responseNotContains("Please log in to access <b>Test: Webform: Access Denied: Submission #$sid</b>.");

    /* ********************************************************************** */
    // Page.
    /* ********************************************************************** */

    // Set access denied to display a dedicated page.
    $webform->setSetting('submission_access_denied', WebformInterface::ACCESS_DENIED_PAGE);
    $webform->setSetting('submission_access_denied_title', 'Webform submission: Access denied');
    $webform->setSetting('submission_access_denied_attributes', ['style' => 'border: 1px solid red', 'class' => [], 'attributes' => []]);
    $webform->save();

    // Check custom access denied page.
    $this->drupalGet("admin/structure/webform/manage/test_form_access_denied/submission/$sid");
    $assert_session->responseNotContains('You are not authorized to access this page.');
    $assert_session->responseContains('<h1>Webform submission: Access denied</h1>');
    $assert_session->responseContains('<div style="border: 1px solid red" class="webform-submission-access-denied">');
    $assert_session->responseContains('Please log in to access <b>Test: Webform: Access Denied: Submission #' . $sid . '</b>.');
  }

}
