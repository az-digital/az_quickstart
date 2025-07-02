<?php

namespace Drupal\Tests\webform_options_limit\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Webform options limit test.
 *
 * @group webform_options_limit
 */
class WebformOptionsLimitUserTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'webform',
    'webform_options_limit',
    'webform_options_limit_test',
  ];

  /**
   * Test options limit user.
   */
  public function testOptionsLimitUserTest() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_handler_options_limit_user');

    // Create authenticated user.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Check that options limit is not met for authenticated user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $assert_session->responseContains('A [1 remaining]');
    $assert_session->responseContains('B [2 remaining]');
    $assert_session->responseContains('C [3 remaining]');
    $assert_session->responseNotContains('options_limit_user is not available.');

    // Check that options limit is reached for authenticated user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $assert_session->responseContains('A [0 remaining]');
    $assert_session->responseContains('B [0 remaining]');
    $assert_session->responseContains('C [0 remaining]');
    $assert_session->responseContains('options_limit_user is not available.');

    // Create another authenticated user.
    $user = $this->drupalCreateUser();
    $this->drupalLogin($user);

    // Check that options limit is not met for authenticated user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $assert_session->responseContains('A [1 remaining]');
    $assert_session->responseContains('B [2 remaining]');
    $assert_session->responseContains('C [3 remaining]');
    $assert_session->responseNotContains('options_limit_user is not available.');

    // Check that options limit is reached for authenticated user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $assert_session->responseContains('A [0 remaining]');
    $assert_session->responseContains('B [0 remaining]');
    $assert_session->responseContains('C [0 remaining]');
    $assert_session->responseContains('options_limit_user is not available.');

    // Logout.
    // NOTE:
    // We are are testing anonymous user last because anonymous
    // submission are transferred to authenticated users when they login.
    $this->drupalLogout();

    // Check that options limit is not met for anonymous user.
    $this->drupalGet('/webform/test_handler_options_limit_user');
    $assert_session->responseContains('A [1 remaining]');
    $assert_session->responseContains('B [2 remaining]');
    $assert_session->responseContains('C [3 remaining]');
    $assert_session->responseNotContains('options_limit_user is not available.');

    // Check that options limit is reached for anonymous user.
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $this->postSubmission($webform);
    $assert_session->responseContains('A [0 remaining]');
    $assert_session->responseContains('B [0 remaining]');
    $assert_session->responseContains('C [0 remaining]');
    $assert_session->responseContains('options_limit_user is not available.');

    // Check that Options limit report is not available.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_handler_options_limit_user/results/options-limit');
    $assert_session->statusCodeEquals(403);
  }

}
