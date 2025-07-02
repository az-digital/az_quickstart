<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for the webform handler invoke alter hook.
 *
 * @group webform
 */
class WebformHandlerInvokeAlterHookTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_handler_invoke_alter'];

  /**
   * Tests webform handler invoke alter hook.
   */
  public function testWebformHandlerInvokeAlterHook() {
    $assert_session = $this->assertSession();

    // Check invoke alter hooks.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::pre_create"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_pre_create_alter() for "contact:email_confirmation"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::pre_create"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_pre_create_alter() for "contact:email_notification"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_elements"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_elements"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::post_create"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::post_create"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::override_settings"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::override_settings"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::prepare_form"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::prepare_form"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::access_element"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::access_element"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_element"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_element"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_confirmation::alter_form"');
    $assert_session->responseContains('Invoking hook_webform_handler_invoke_alter() for "contact:email_notification::alter_form"');
  }

}
