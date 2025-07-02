<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for label element.
 *
 * @group webform
 */
class WebformElementLabelTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_label'];

  /**
   * Test label element.
   */
  public function testMarkup() {
    $assert_session = $this->assertSession();

    // Get form.
    $this->drupalGet('/webform/test_element_label');
    // Check label display on form.
    $assert_session->responseContains('<label data-drupal-selector="edit-label" for="edit-label">This is normal label</label>');
    $assert_session->responseContains('<label data-drupal-selector="edit-label-form" for="edit-label-form">This is only displayed on the form view.</label>');
    $assert_session->responseNotContains('<label data-drupal-selector="edit-label-view" for="edit-label-view">This is only displayed on the submission view.</label>');
    $assert_session->responseContains('<label data-drupal-selector="edit-label-both" for="edit-label-both">This is displayed on the both the form and submission view.</label>');
    // Check custom label with required.
    $assert_session->responseContains('<label style="color: green" data-drupal-selector="edit-label-custom" for="edit-label-custom" class="js-form-required form-required">This is a customized label</label>');
    $this->drupalGet('/webform/test_element_label');

    // Get preview.
    $this->submitForm([], 'Preview');
    // Check label display on view.
    $assert_session->responseNotContains('<label>This is normal label</label>');
    $assert_session->responseNotContains('<label>This is only displayed on the form view.</label>');
    $assert_session->responseContains('<label>This is only displayed on the submission view.</label>');
    $assert_session->responseContains('<label>This is displayed on the both the form and submission view.</label>');
    // Check custom label with required removed.
    $assert_session->responseContains('<label style="color: green">This is a customized label</label>');
  }

}
