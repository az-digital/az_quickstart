<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission form preview.
 *
 * @group webform
 */
class WebformSettingsPreviewTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_preview'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Exclude Progress tracker so that the default progress bar is displayed.
    // The default progress bar is most likely never going to change.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('libraries.excluded_libraries', ['progress-tracker'])
      ->save();
  }

  /**
   * Tests webform webform submission form preview.
   */
  public function testPreview() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform_preview = Webform::load('test_form_preview');

    // Check webform with optional preview.
    $this->drupalGet('/webform/test_form_preview');
    $assert_session->buttonExists('Submit');
    $assert_session->buttonExists('Preview');

    // Check default preview with values.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => 'test', 'email' => 'example@example.com', 'checkbox' => TRUE];
    $this->submitForm($edit, 'Preview');

    $assert_session->responseContains('<h1>Test: Webform: Preview: Preview</h1>');

    $assert_session->responseContains('<b class="webform-progress-bar__page-title">Preview</b></li>');

    $assert_session->responseContains('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    $assert_session->buttonExists('Submit');
    $assert_session->buttonExists('< Previous');

    $assert_session->responseContains('<div class="webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');
    $assert_session->responseContains('<div data-drupal-selector="edit-submission" class="webform-submission-data webform-submission-data--webform-test-form-preview webform-submission-data--view-mode-preview">');
    $assert_session->responseContains('<fieldset class="format-attributes-class webform-container webform-container-type-fieldset js-form-item form-item js-form-wrapper form-wrapper" id="test_form_preview--fieldset">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-textfield js-form-item form-item form-type-item js-form-type-item form-item-name js-form-item-name" id="test_form_preview--name">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-textfield js-form-item form-item js-form-type-item form-item-name js-form-item-name" id="test_form_preview--name">'),
    );
    $assert_session->responseContains('<label>Name</label>' . PHP_EOL . '        test');

    $assert_session->responseContains('<section class="format-attributes-class js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_form_preview--container">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item form-type-item js-form-type-item form-item-email js-form-item-email" id="test_form_preview--email">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item js-form-type-item form-item-email js-form-item-email" id="test_form_preview--email">'),
    );
    $assert_session->responseContains('<label>Email</label>' . PHP_EOL . '        <a href="mailto:example@example.com">example@example.com</a>');

    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-checkbox js-form-item form-item form-type-item js-form-type-item form-item-checkbox js-form-item-checkbox" id="test_form_preview--checkbox">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-checkbox js-form-item form-item js-form-type-item form-item-checkbox js-form-item-checkbox" id="test_form_preview--checkbox">'),
    );
    $assert_session->responseContains('<section class="format-attributes-class js-form-item form-item js-form-wrapper form-wrapper webform-section" id="test_form_preview--section">');
    $assert_session->responseContains('<label>Checkbox</label>' . PHP_EOL . '        Yes');
    $assert_session->responseContains('<div class="webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');

    // Check default preview without values.
    $this->drupalGet('/webform/test_form_preview');
    $this->submitForm([], 'Preview');
    $assert_session->responseNotContains('<label>Name</label>');
    $assert_session->responseNotContains('<label>Email</label>');
    $assert_session->responseNotContains('<label>Checkbox</label>');

    // Check submission view without values.
    $sid = $this->postSubmission($webform_preview);
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid");
    $assert_session->responseNotContains('<label>Name</label>');
    $assert_session->responseNotContains('<label>Email</label>');
    $assert_session->responseNotContains('<label>Checkbox</label>');

    // Check submission table without values.
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid/table");
    $assert_session->responseNotContains('<th>Name</th>');
    $assert_session->responseNotContains('<th>Email</th>');
    $assert_session->responseNotContains('<th>Checkbox</th>');
    $assert_session->responseNotContains('<td>No</td>');

    // Clear default preview message.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_preview_message', '')
      ->save();

    // Check blank preview message is not displayed.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => 'test', 'email' => 'example@example.com'];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseNotContains('Please review your submission. Your submission is not complete until you press the "Submit" button!');

    // Set preview and submission to include empty.
    $webform_preview->setSetting('preview_exclude_empty', FALSE);
    $webform_preview->setSetting('preview_exclude_empty_checkbox', FALSE);
    $webform_preview->setSetting('submission_exclude_empty', FALSE);
    $webform_preview->setSetting('submission_exclude_empty_checkbox', FALSE);
    $webform_preview->save();

    // Check empty elements are included in preview.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => '', 'email' => '', 'checkbox' => FALSE];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseContains('<label>Name</label>' . PHP_EOL . '        {Empty}');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item form-type-item js-form-type-item form-item-email js-form-item-email" id="test_form_preview--email">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="format-attributes-class webform-element webform-element-type-email js-form-item form-item js-form-type-item form-item-email js-form-item-email" id="test_form_preview--email">'),
    );
    $assert_session->responseContains('<label>Email</label>' . PHP_EOL . '        {Empty}');
    $assert_session->responseContains('<label>Checkbox</label>' . PHP_EOL . '        No');

    // Check empty elements are included in submission view.
    $sid = $this->postSubmission($webform_preview);
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid");
    $assert_session->responseContains('<label>Name</label>');
    $assert_session->responseContains('<label>Email</label>');
    $assert_session->responseContains('<label>Checkbox</label>');

    // Check submission table without values.
    $this->drupalGet("admin/structure/webform/manage/test_form_preview/submission/$sid/table");
    $assert_session->responseContains('<th>Name</th>');
    $assert_session->responseContains('<th>Email</th>');
    $assert_session->responseContains('<th>Checkbox</th>');
    $assert_session->responseContains('<td>No</td>');

    // Add special character to title.
    $webform_preview->set('title', "This has special characters. '<>\"&");
    $webform_preview->save();

    // Check special characters in form page title.
    $this->drupalGet('/webform/test_form_preview');
    $assert_session->responseContains('<title>This has special characters. \'"& | Drupal</title>');
    $assert_session->responseContains('<h1>This has special characters. &#039;&lt;&gt;&quot;&amp;</h1>');

    // Check special characters in preview page title.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => 'test'];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseContains('<title>This has special characters. \'"&: Preview | Drupal</title>');
    $assert_session->responseContains('<h1>This has special characters. &#039;&lt;&gt;&quot;&amp;: Preview</h1>');

    // Check required preview with custom settings.
    $webform_preview->setSettings([
      'preview' => DRUPAL_REQUIRED,
      'preview_label' => '{Label}',
      'preview_title' => '{Title}',
      'preview_message' => '{Message}',
      'preview_attributes' => ['class' => ['preview-custom']],
      'preview_excluded_elements' => ['email' => 'email'],
    ]);

    // Add 'webform_actions' element.
    $webform_preview->setElementProperties('actions', [
      '#type' => 'webform_actions',
      '#preview_next__label' => '{Preview}',
      '#preview_prev__label' => '{Back}',
    ]);
    $webform_preview->save();

    // Check custom preview.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => 'test'];
    $this->submitForm($edit, '{Preview}');
    $assert_session->responseContains('<h1>{Title}</h1>');
    $assert_session->responseContains('<b class="webform-progress-bar__page-title">{Label}</b></li>');
    $assert_session->responseContains('{Message}');
    $assert_session->buttonExists('Submit');
    $assert_session->buttonExists('{Back}');
    $assert_session->responseContains('<label>Name</label>' . PHP_EOL . '        test');
    $assert_session->responseNotContains('<label>Email</label>');
    $assert_session->responseContains('<div class="preview-custom webform-preview js-form-wrapper form-wrapper" data-drupal-selector="edit-preview" id="edit-preview">');

    $this->drupalGet('/webform/test_form_preview');
    $assert_session->buttonNotExists('Submit');
    $assert_session->buttonExists('{Preview}');

    // Check empty element is excluded from preview.
    $this->drupalGet('/webform/test_form_preview');
    $edit = ['name' => 'test', 'email' => ''];
    $this->submitForm($edit, '{Preview}');
    $assert_session->responseContains('<label>Name</label>' . PHP_EOL . '        test');
    $assert_session->responseNotContains('<label>Email</label>');
  }

}
