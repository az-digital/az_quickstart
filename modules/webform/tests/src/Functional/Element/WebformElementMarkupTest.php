<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for markup element.
 *
 * @group webform
 */
class WebformElementMarkupTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_markup'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_markup'];

  /**
   * Test markup element.
   */
  public function testMarkup() {
    $assert_session = $this->assertSession();

    // Check markup display on form.
    $this->drupalGet('/webform/test_element_markup');
    $this->assertCssSelect('.js-form-item-markup.form-item-markup.form-no-label');
    $assert_session->responseContains('<p>This is normal markup</p>');
    $assert_session->responseContains('<p>This is only displayed on the form view.</p>');
    $assert_session->responseNotContains('<p>This is only displayed on the submission view.</p>');
    $assert_session->responseContains('<p>This is displayed on the both the form and submission view.</p>');
    $assert_session->responseContains('<p>This is displayed on the both the form and submission view.</p>');

    // Check markup alter via preprocessing.
    // @see webform_test_markup_preprocess_webform_html_editor_markup()
    $this->drupalGet('/webform/test_element_markup');
    $assert_session->responseNotContains('<p>Alter this markup.</p>');
    $assert_session->responseContains('<p><em>Alter this markup.</em> <strong>This markup was altered.</strong></p>');

    // Check markup display on view.
    $this->drupalGet('/webform/test_element_markup');
    $this->submitForm([], 'Preview');
    $assert_session->responseNotContains('<p>This is normal markup</p>');
    $assert_session->responseNotContains('<p>This is only displayed on the form view.</p>');
    $assert_session->responseContains('<p>This is only displayed on the submission view.</p>');
    $assert_session->responseContains('<p>This is displayed on the both the form and submission view.</p>');
  }

}
