<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform boolean limit test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitBooleanTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit.
   */
  public function testOptionsLimit() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_boolean_limit');

    $this->drupalGet('/webform/test_handler_boolean_limit');

    // Check that boolean default is available.
    $assert_session->responseContains('<label for="edit-boolean-limit-default" class="option">boolean_limit_default [1 remaining]</label>');

    // Check that boolean message is available.
    $assert_session->responseContains('<div id="edit-boolean-limit-message--description" class="webform-element-description">2 options remaining / 2 limit / 0 total</div>');

    // Check that boolean remove is available.
    $assert_session->responseContains('<label for="edit-boolean-limit-remove" class="option">boolean_limit_remove [3 remaining]</label>');

    // Post first submission.
    $sid_1 = $this->postSubmission($webform);

    // Check that boolean default is not available.
    $assert_session->responseContains('<label for="edit-boolean-limit-default" class="option">boolean_limit_default [0 remaining]</label>');
    $this->assertCssSelect('#edit-boolean-limit-default[disabled]');
    $assert_session->responseContains('boolean_limit_default is not available.');

    // Check that boolean message is updated and available.
    $assert_session->responseContains('<div id="edit-boolean-limit-message--description" class="webform-element-description">1 option remaining / 2 limit / 1 total</div>');

    // Check that boolean remove is updated and available.
    $this->assertCssSelect('#edit-boolean-limit-remove');
    $assert_session->responseContains('<label for="edit-boolean-limit-remove" class="option">boolean_limit_remove [2 remaining]</label>');

    // Post two more submissions.
    $this->postSubmission($webform);
    $this->postSubmission($webform);

    // Check that boolean default is not available.
    $assert_session->responseContains('<label for="edit-boolean-limit-default" class="option">boolean_limit_default [0 remaining]</label>');
    $this->assertCssSelect('#edit-boolean-limit-default[disabled]');
    $assert_session->responseContains('boolean_limit_default is not available.');

    // Check that boolean message is not available.
    $this->assertCssSelect('#edit-boolean-limit-message[disabled]');
    $assert_session->responseContains('boolean_limit_message is not available.');

    // Check that boolean remove is removed.
    $this->assertNoCssSelect('#edit-boolean-limit-remove');

    // Login as an admin.
    $this->drupalLogin($this->rootUser);

    // Check that existing submission values are not disabled.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_boolean_limit/submission/$sid_1/edit");
    $this->assertNoCssSelect('#edit-boolean-limit-default[disabled]');
    $this->assertNoCssSelect('#edit-boolean-limit-message[disabled]');
    $this->assertCssSelect('#edit-boolean-limit-remove');
  }

}
