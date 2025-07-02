<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform handler plugin.
 *
 * @group webform
 */
class WebformHandlerTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_handler'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_test'];

  /**
   * Tests webform handler plugin.
   */
  public function testWebformHandler() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Get the webform test handler.
    /** @var \Drupal\webform\WebformInterface $webform_handler_test */
    $webform_handler_test = Webform::load('test_handler_test');

    // Check new submission plugin invoking.
    $this->drupalGet('/webform/test_handler_test');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');

    // Check validate submission plugin invoked and displaying an error.
    $this->postSubmission($webform_handler_test, ['element' => 'a value']);
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseContains('The element must be empty. You entered <em class="placeholder">a value</em>.');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');
    $assert_session->responseNotContains('One two one two this is just a test');

    // Check submit submission plugin invoking.
    $sid = $this->postSubmission($webform_handler_test);
    $webform_submission = WebformSubmission::load($sid);
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $assert_session->responseContains('One two one two this is just a test');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preprocessConfirmation');
    $assert_session->responseContains('<div class="webform-confirmation__message">::preprocessConfirmation</div>');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');

    // Check confirmation with token.
    $this->drupalGet('/webform/test_handler_test/confirmation', ['query' => ['token' => $webform_submission->getToken()]]);
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preprocessConfirmation');
    $assert_session->responseContains('<div class="webform-confirmation__message">::preprocessConfirmation</div>');

    // Check confirmation without token.
    $this->drupalGet('/webform/test_handler_test/confirmation');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preprocessConfirmation');
    $assert_session->responseContains('<div class="webform-confirmation__message">::preprocessConfirmation</div>');

    // Check update submission plugin invoking.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/edit');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave update');

    // Check delete submission plugin invoking.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/delete');
    $this->submitForm([], 'Delete');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:accessSubmission');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preDelete');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postDelete');
    $assert_session->responseContains('<em class="placeholder">Test: Handler: Test invoke methods: Submission #' . $webform_submission->serial() . '</em> has been deleted.');

    // Check submission access returns forbidden when element value is set to 'submission_access_denied'.
    $sid = $this->postSubmission($webform_handler_test, ['element' => 'submission_access_denied']);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid);
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/edit');
    $assert_session->statusCodeEquals(403);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/delete');
    $assert_session->statusCodeEquals(403);

    // Check allowed access when element value is set to 'access_allowed'.
    $sid = $this->postSubmission($webform_handler_test, ['element' => 'access_allowed']);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseNotContains('<label>element</label>');
    $assert_session->responseContains('access_allowed');
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/edit');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldValueEquals('element', 'access_allowed');

    // Check element access returns forbidden when element value is set to 'element_access_denied'.
    $sid = $this->postSubmission($webform_handler_test, ['element' => 'element_access_denied']);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid);
    $assert_session->statusCodeEquals(200);
    $assert_session->responseNotContains('<label>element</label>');
    $assert_session->responseNotContains('element_access_denied');
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/submission/' . $sid . '/edit');
    $assert_session->statusCodeEquals(200);
    $assert_session->fieldNotExists('element');

    // Check configuration settings.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/test/edit');
    $edit = ['settings[message]' => '{message}'];
    $this->submitForm($edit, 'Save');
    $this->postSubmission($webform_handler_test);
    $assert_session->responseContains('{message}');

    // Check disabling a handler.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/test/edit');
    $edit = ['status' => FALSE];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('/webform/test_handler_test');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Enable the handler and disable the saving of results.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/test/edit');
    $edit = ['status' => TRUE];
    $this->submitForm($edit, 'Save');
    $webform_handler_test->setSettings(['results_disabled' => TRUE]);
    $webform_handler_test->save();

    // Check webform disabled with saving of results is disabled and handler does
    // not process results.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_handler_test');
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseNotContains('This webform is not saving or handling any submissions. All submitted data will be lost.');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check admin can still post submission.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_handler_test');
    $assert_session->buttonExists('Submit');
    $assert_session->responseContains('This webform is currently not saving any submitted data.');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check submit submission plugin invoking when saving results is disabled.
    $webform_handler_test->setSetting('results_disabled', TRUE);
    $webform_handler_test->save();
    $this->postSubmission($webform_handler_test);
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:validateForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:submitForm');
    $assert_session->responseContains('One two one two this is just a test');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:confirmForm');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preSave');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postSave insert');
    // Check that post load is not executed when saving results is disabled.
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postLoad');

    /* ********************************************************************** */
    // Handler.
    /* ********************************************************************** */

    // Check update handler.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/test/edit');
    $this->submitForm([], 'Save');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateHandler');
    $assert_session->responseContains('The webform handler was successfully updated.');

    // Check delete handler.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/test/delete');
    $this->submitForm([], 'Delete');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteHandler');

    // Check create handler.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/add/test');
    $edit = ['handler_id' => 'test'];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('The webform handler was successfully added.');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createHandler');

    /* ********************************************************************** */
    // Single handler.
    /* ********************************************************************** */

    // Check test handler is executed.
    $this->drupalGet('/webform/test_handler_test/test');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterForm');

    // Check test handler is enabled and debug handler is disabled.
    $this->drupalGet('/webform/test_handler_test/test');
    $edit = ['element' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('One two one two this is just a test');
    $assert_session->responseNotContains("element: ''");

    // Check test handler is disabled.
    $this->drupalGet('/webform/test_handler_test/test', ['query' => ['_webform_handler' => 'debug']]);
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:preCreate');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:postCreate');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElements');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:alterElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:overrideSettings');
    $assert_session->responseContains('Testing the <em class="placeholder">Test: Handler: Test invoke methods</em> webform <em class="placeholder">Debug</em> handler. <strong>All other emails/handlers are disabled.</strong>');

    // Check test handler is now disabled and debug handler is enabled.
    $options = ['query' => ['_webform_handler' => 'debug']];
    $this->drupalGet('/webform/test_handler_test/test', $options);
    $edit = ['element' => ''];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('One two one two this is just a test');
    $assert_session->responseContains("element: ''");

    // Check 403 access denied for missing handler.
    $this->drupalGet('/webform/test_handler_test/test', ['query' => ['_webform_handler' => 'missing']]);
    $assert_session->statusCodeEquals(403);
    $assert_session->responseContains('The <em class="placeholder">missing</em> email/handler for the <em class="placeholder">Test: Handler: Test invoke methods</em> webform does not exist.');

    /* ********************************************************************** */
    // Off-canvas width.
    /* ********************************************************************** */

    // Check add off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/add');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/test_handler_test/handlers/add/test_offcanvas_width"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/test_handler_test/handlers/add/test_offcanvas_width"][data-dialog-options*="550"]');

    // Add handler.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers/add/test_offcanvas_width');
    $edit = ['handler_id' => 'test_offcanvas_width'];
    $this->submitForm($edit, 'Save');

    // Check edit off-canvas element width is 800.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test/handlers');
    $this->assertCssSelect('[href$="/admin/structure/webform/manage/test_handler_test/handlers/test_offcanvas_width/edit"][data-dialog-options*="800"]');
    $this->assertNoCssSelect('[href$="/admin/structure/webform/manage/test_handler_test/handlers/test_offcanvas_width/edit"][data-dialog-options*="550"]');
  }

  /**
   * Tests webform handler element plugin.
   */
  public function testWebformHandlerElement() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check CRUD methods invoked.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test');
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error",
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check create element.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test');
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'
test:
  '#type': textfield",
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check update element.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test');
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'
test:
  '#type': textfield
  '#title': Test",
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');

    // Check delete element.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_test');
    $edit = [
      'elements' => "element:
  '#type': textfield
  '#title': 'Empty element'
  '#description': 'Entering any value will throw an error'",
    ];
    $this->submitForm($edit, 'Save');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:createElement');
    $assert_session->responseNotContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:updateElement');
    $assert_session->responseContains('Invoked test: Drupal\webform_test_handler\Plugin\WebformHandler\TestWebformHandler:deleteElement');
  }

}
