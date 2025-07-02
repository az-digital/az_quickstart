<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for telephone element.
 *
 * @group webform
 */
class WebformElementTelephoneTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'telephone_validation'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_telephone'];

  /**
   * Test telephone element.
   */
  public function testTelephone() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_telephone');

    // Check basic tel.
    $assert_session->responseContains('<input data-drupal-selector="edit-tel-default" type="tel" id="edit-tel-default" name="tel_default" value="" size="30" maxlength="128" class="form-tel" />');

    // Check international tel.
    $assert_session->responseContains('<input class="js-webform-telephone-international webform-webform-telephone-international form-tel" data-drupal-selector="edit-tel-international" type="tel" id="edit-tel-international" name="tel_international" value="" size="30" maxlength="128" />');

    // Check international telephone validation.
    $assert_session->responseContains('<input data-drupal-selector="edit-tel-validation-e164" type="tel" id="edit-tel-validation-e164" name="tel_validation_e164" value="" size="30" maxlength="128" class="form-tel" />');

    // Check USE telephone validation.
    $assert_session->responseContains('<input data-drupal-selector="edit-tel-validation-national" aria-describedby="edit-tel-validation-national--description" type="tel" id="edit-tel-validation-national" name="tel_validation_national" value="" size="30" maxlength="128" class="form-tel" />');

    // Make the telephone_validation.module is installed.
    if (!\Drupal::moduleHandler()->moduleExists('telephone_validation')) {
      return;
    }

    // Check telephone validation missing plus sign.
    $this->drupalGet('/webform/test_element_telephone');
    $edit = [
      'tel_validation_e164' => '12024561111',
      'tel_validation_national' => '12024561111',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The phone number <em class="placeholder">12024561111</em> is not valid.');

    // Check telephone validation with plus sign.
    $this->drupalGet('/webform/test_element_telephone');
    $edit = [
      'tel_validation_e164' => '+12024561111',
      'tel_validation_national' => '+12024561111',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('The phone number <em class="placeholder">12024561111</em> is not valid.');

    // Check telephone validation with non US number.
    $this->drupalGet('/webform/test_element_telephone');
    $edit = [
      'tel_validation_national' => '+74956970349',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The phone number <em class="placeholder">+74956970349</em> is not valid.');
  }

}
