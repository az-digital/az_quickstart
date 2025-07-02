<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform terms of service element.
 *
 * @group webform
 */
class WebformElementTermsOfServiceTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_terms_of_service'];

  /**
   * Tests TermsOfService element.
   */
  public function testTermsOfService() {
    $assert_session = $this->assertSession();

    // Check rendering.
    $this->drupalGet('/webform/test_element_terms_of_service');

    // Check modal.
    $this->assertCssSelect('[data-webform-terms-of-service-type="modal"].form-item-terms-of-service-default');
    $assert_session->responseContains('<input data-drupal-selector="edit-terms-of-service-default" type="checkbox" id="edit-terms-of-service-default" name="terms_of_service_default" value class="form-checkbox required" required="required" aria-required="true" />');
    $assert_session->responseContains('<label for="edit-terms-of-service-default" class="option js-form-required form-required">I agree to the <a role="button" href="#terms">terms of service</a>. (default)</label>');
    $assert_session->responseContains('<div id="edit-terms-of-service-default--description" class="webform-element-description">');
    $assert_session->responseContains('<div id="webform-terms-of-service-terms_of_service_default--description" class="webform-terms-of-service-details js-hide">');
    $assert_session->responseContains('<div class="webform-terms-of-service-details--title">terms_of_service_default</div>');
    $assert_session->responseContains('<div class="webform-terms-of-service-details--content">These are the terms of service.</div>');

    // Check slideout.
    $assert_session->responseContains('<label for="edit-terms-of-service-slideout" class="option">I agree to the <a role="button" href="#terms">terms of service</a>. (slideout)</label>');

    // Check validation.
    $this->drupalGet('/webform/test_element_terms_of_service');
    $this->submitForm([], 'Preview');
    $assert_session->responseContains('I agree to the terms of service. (default) field is required.');

    // Check preview.
    $this->drupalGet('/webform/test_element_terms_of_service');
    $edit = [
      'terms_of_service_default' => TRUE,
      'terms_of_service_modal' => TRUE,
      'terms_of_service_slideout' => TRUE,
    ];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseContains('I agree to the terms of service. (default)');
    $assert_session->responseContains('I agree to the terms of service. (modal)');
    $assert_session->responseContains('I agree to the terms of service. (slideout)');

    // Check default title and auto incremented key.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_element_terms_of_service/element/add/webform_terms_of_service');
    $assert_session->fieldValueEquals('key', 'terms_of_service_01');
    $assert_session->fieldValueEquals('properties[title]', 'I agree to the {terms of service}.');
  }

}
