<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform archived.
 *
 * @group webform
 */
class WebformSettingsArchivedTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_node', 'webform_templates', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_archived'];

  /**
   * Test webform submission form archived.
   */
  public function testArchived() {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_archived');

    // Check that archived webform is removed from webforms manage page.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseContains('<td><a href="' . $base_path . 'form/contact">Contact</a></td>');
    $assert_session->responseNotContains('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a></td>');

    // Check that archived webform appears when archived filter selected.
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $assert_session->responseNotContains('<td><a href="' . $base_path . 'form/contact">Contact</a></td>');
    $assert_session->responseContains('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a></td>');

    // Check that archived webform displays archive message.
    $this->drupalGet('/form/test-form-archived');
    $assert_session->responseContains('This webform is <a href="' . $base_path . 'admin/structure/webform/manage/test_form_archived/settings">archived</a>');

    // Check that archived webform is remove webform select menu.
    $this->drupalGet('/node/add/webform');
    $assert_session->responseContains('<option value="contact">Contact</option>');
    $assert_session->responseNotContains('Test: Webform: Archive');

    // Check that selected archived webform is preserved in webform select menu.
    $this->drupalGet('/node/add/webform', ['query' => ['webform_id' => 'test_form_archived']]);
    $assert_session->responseContains('<option value="contact">Contact</option>');
    $assert_session->responseContains('<optgroup label="Archived"><option value="test_form_archived" selected="selected">Test: Webform: Archive</option></optgroup>');

    // Change the archived webform to be a template.
    $webform->set('template', TRUE);
    $webform->save();

    // Change archived webform to template.
    $this->drupalGet('/admin/structure/webform');
    $assert_session->responseContains('Contact');
    $assert_session->responseNotContains('Test: Webform: Archive');

    // Check that archived template with (Template) label appears when archived filter selected.
    $this->drupalGet('/admin/structure/webform', ['query' => ['state' => 'archived']]);
    $assert_session->responseNotContains('Contact');
    $assert_session->responseContains('<td><a href="' . $base_path . 'form/test-form-archived">Test: Webform: Archive</a> <b>(Template)</b></td>');

    // Check that archived template displays archive message
    // (not template message).
    $this->drupalGet('/form/test-form-archived');
    $assert_session->responseContains('This webform is <a href="' . $base_path . 'admin/structure/webform/manage/test_form_archived/settings">archived</a>');
  }

}
