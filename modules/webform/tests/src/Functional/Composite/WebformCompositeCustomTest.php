<?php

namespace Drupal\Tests\webform\Functional\Composite;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for custom composite element.
 *
 * @group webform
 */
class WebformCompositeCustomTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite_custom'];

  /**
   * Test custom composite element.
   */
  public function testCustom() {
    $assert_session = $this->assertSession();

    /* Display */

    $this->drupalGet('/webform/test_composite_custom');

    // Check basic custom composite.
    $assert_session->responseContains('<label>webform_custom_composite_basic</label>');
    $assert_session->responseContains('<div id="webform_custom_composite_basic_table">');
    $assert_session->responseContains('<div class="webform-multiple-table webform-multiple-table-responsive">');
    $assert_session->responseContains('<th class="webform_custom_composite_basic-table--handle webform-multiple-table--handle"><span class="visually-hidden">Re-order</span></th>');
    $assert_session->responseContains('<th class="webform_custom_composite_basic-table--first_name webform-multiple-table--first_name">First name</th>');
    $assert_session->responseContains('<th class="webform_custom_composite_basic-table--last_name webform-multiple-table--last_name">Last name</th>');
    $assert_session->responseContains('<th class="webform_custom_composite_basic-table--weight webform-multiple-table--weight">Weight</th>');

    // Check advanced custom composite.
    $assert_session->responseContains('<span class="field-suffix"> yrs. old</span>');

    // Check composite in fieldset.
    $assert_session->responseContains('<fieldset class="fieldgroup form-composite js-webform-type-webform-custom-composite webform-type-webform-custom-composite js-form-item form-item js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-custom-composite-fieldset" id="edit-webform-custom-composite-fieldset">');
    $assert_session->responseContains('<span class="fieldset-legend">webform_custom_composite_fieldset</span>');

    // Check composite in container.
    $assert_session->responseContains('<div id="webform_custom_composite_container_table"><div class="custom-class js-form-wrapper form-wrapper" data-drupal-selector="edit-webform-custom-composite-container" id="edit-webform-custom-composite-container">');

    /* Processing */

    // Check contact composite value.
    $this->drupalGet('/webform/test_composite_custom');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_custom_composite_basic:
  - first_name: John
    last_name: Smith
webform_custom_composite_advanced:
  - first_name: John
    last_name: Smith
    sex: Male
    martial_status: Single
    employment_status: Unemployed
    age: '20'");
  }

}
