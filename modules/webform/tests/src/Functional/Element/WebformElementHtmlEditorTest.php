<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform HTML editor element.
 *
 * @see \Drupal\Tests\webform\Functional\Access\WebformAccessFilterFormatTest
 * @group webform
 */
class WebformElementHtmlEditorTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['editor', 'ckeditor', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_html_editor'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests HTML Editor element.
   */
  public function testHtmlEditor() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* Element text format */

    $webform = Webform::load('test_element_html_editor');

    // Check required validation.
    $edit = [
      'webform_html_editor[value][value]' => '',
      'webform_html_editor_format[value][value]' => '',
      'webform_html_editor_codemirror[value]' => '',
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('webform_html_editor (default) field is required.');
    $assert_session->responseContains('webform_html_editor (format) field is required.');
    $assert_session->responseContains('webform_html_editor_codemirror (none) field is required.');

    $this->drupalGet('/webform/test_element_html_editor');

    // Check that HTML editor is enabled.
    $assert_session->responseContains('<textarea data-drupal-selector="edit-webform-html-editor-value-value" class="webform-html-editor-default-filter-format form-textarea required" id="edit-webform-html-editor-value-value" name="webform_html_editor[value][value]" rows="5" cols="60" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that CodeMirror is displayed when #format: FALSE.
    $assert_session->responseContains('<textarea data-drupal-selector="edit-webform-html-editor-codemirror-value" class="js-webform-codemirror webform-codemirror html required form-textarea" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-codemirror-value" name="webform_html_editor_codemirror[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that attributes are support by the default 'webform' filter format.
    $build = WebformHtmlEditor::checkMarkup('<p class="other">Some text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), '<p class="other">Some text</p>');

    // Disable HTML editor.
    $this->drupalGet('/admin/structure/webform/config/elements');
    $edit = ['html_editor[disabled]' => TRUE];
    $this->submitForm($edit, 'Save configuration');

    // Check that HTML editor is removed and replaced by CodeMirror HTML editor.
    $this->drupalGet('/webform/test_element_html_editor');
    $assert_session->responseNotContains('<textarea data-drupal-selector="edit-webform-html-editor-value-value" id="edit-webform-html-editor-value-value" name="webform_html_editor[value][value]" rows="5" cols="60" class="form-textarea required" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html required form-textarea" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that attributes are support when the HTML editor is disabled.
    $build = WebformHtmlEditor::checkMarkup('<p class="other">Some text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), '<p class="other">Some text</p>');

    // Enable HTML editor and element text format.
    $this->drupalGet('/admin/structure/webform/config/elements');
    $edit = [
      'html_editor[disabled]' => FALSE,
      'html_editor[element_format]' => 'basic_html',
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check that Text format is disabled.
    $this->drupalGet('/webform/test_element_html_editor');
    $assert_session->responseNotContains('<textarea class="js-html-editor form-textarea" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $assert_session->responseNotContains('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html required form-textarea" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-webform-html-editor-value-value" id="edit-webform-html-editor-value-value" name="webform_html_editor[value][value]" rows="5" cols="60" class="form-textarea required" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that attributes are NOT support by the basic_html filter format.
    $build = WebformHtmlEditor::checkMarkup('<p class="other">Some text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), '<p>Some text</p>');

    // Check that tidy removed <p> tags.
    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), 'Some text');

    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p><p>More text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), '<p>Some text</p><p>More text</p>');

    // Disable HTML tidy.
    $this->drupalGet('/admin/structure/webform/config/elements');
    $this->submitForm(['html_editor[tidy]' => FALSE], 'Save configuration');

    // Check that tidy is disabled.
    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p>');
    $this->assertEquals(\Drupal::service('renderer')->renderPlain($build), '<p>Some text</p>');

    /* Email text format */

    // Check that HTML editor is used.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/email_confirmation/edit');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-settings-body-custom-html-value-value" class="webform-html-editor-default-filter-format form-textarea" id="edit-settings-body-custom-html-value-value" name="settings[body_custom_html][value][value]" rows="5" cols="60">');

    // Enable mail text format.
    $edit = ['html_editor[mail_format]' => 'basic_html'];
    $this->drupalGet('/admin/structure/webform/config/elements');
    $this->submitForm($edit, 'Save configuration');

    // Check mail text format is used.
    $this->drupalGet('/admin/structure/webform/manage/contact/handlers/email_confirmation/edit');
    $assert_session->responseNotContains('<textarea data-drupal-selector="edit-settings-body-custom-html-value" class="js-html-editor form-textarea" id="edit-settings-body-custom-html-value" name="settings[body_custom_html][value]" rows="5" cols="60">');
    $assert_session->responseContains('<textarea data-drupal-selector="edit-settings-body-custom-html-value-value" id="edit-settings-body-custom-html-value-value" name="settings[body_custom_html][value][value]" rows="5" cols="60" class="form-textarea">');
    $assert_session->responseContains('<div class="js-filter-wrapper js-form-wrapper form-wrapper" data-drupal-selector="edit-settings-body-custom-html-value-format" id="edit-settings-body-custom-html-value-format">');
  }

}
