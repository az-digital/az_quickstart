<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;

/**
 * Tests for webform alter hooks.
 *
 * @group webform
 */
class WebformAlterHooksTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_node', 'webform_test_alter_hooks'];

  /**
   * Tests webform alter hooks.
   */
  public function testWebformAlterHooks() {
    $assert_session = $this->assertSession();

    // Check webform alter hooks.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains("hook_webform_submission_form_alter(): 'webform_submission_contact_add_form' executed.");
    $assert_session->responseContains("hook_form_alter(): 'webform_submission_contact_add_form' executed.");
    $assert_session->responseContains("hook_form_webform_submission_BASE_FORM_ID_form_alter(): 'webform_submission_contact_add_form' executed.");
    $assert_session->responseContains("hook_form_webform_submission_FORM_ID_form_alter(): 'webform_submission_contact_add_form' executed.");

    // Check webform element alter hooks.
    $assert_session->responseContains("hook_webform_element_alter(): 'name' executed.");
    $assert_session->responseContains("hook_webform_element_alter(): 'email' executed.");
    $assert_session->responseContains("hook_webform_element_ELEMENT_TYPE_alter(): 'email' executed.");
    $assert_session->responseContains("hook_webform_element_alter(): 'subject' executed.");
    $assert_session->responseContains("hook_webform_element_alter(): 'message' executed.");

    // Check webform node alter hooks.
    $webform_node = $this->createWebformNode('contact');
    $nid = $webform_node->id();
    $this->drupalGet("node/$nid");
    $assert_session->responseContains("hook_webform_submission_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
    $assert_session->responseContains("hook_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
    $assert_session->responseContains("hook_form_webform_submission_BASE_FORM_ID_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
    $assert_session->responseContains("hook_form_webform_submission_FORM_ID_form_alter(): 'webform_submission_contact_node_{$nid}_add_form' executed.");
  }

}
