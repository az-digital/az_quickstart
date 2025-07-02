<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for range element.
 *
 * @group webform
 */
class WebformElementRangeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_range'];

  /**
   * Test range element.
   */
  public function testRating() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_range');

    // Check basic range element.
    $assert_session->responseContains('<input data-drupal-selector="edit-range" type="range" id="edit-range" name="range" value="" step="1" min="0" max="100" class="form-range" />');

    // Check advanced range element.
    $assert_session->responseContains('<label for="edit-range-advanced">range_advanced</label>');
    $assert_session->responseContains('<span class="field-prefix">-100</span>');
    $assert_session->responseContains('<input style="width: 400px" data-drupal-selector="edit-range-advanced" type="range" id="edit-range-advanced" name="range_advanced" value="" step="1" min="-100" max="100" class="form-range" />');
    $assert_session->responseContains('<span class="field-suffix">100</span>');

    // Check output above range element.
    $assert_session->responseContains('<output for="range_output_above" data-display="above"></output>');

    // Check output below with custom range element.
    $assert_session->responseContains('<output style="background-color: yellow" for="range_output_below" data-display="below" data-field-prefix="$" data-field-suffix=".00"></output>');

    // Check output left range element.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<span class="field-prefix"><div class="js-form-item form-item form-type-number js-form-type-number form-item-range-output-left__output js-form-item-range-output-left__output form-no-label">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<span class="field-prefix"><div class="js-form-item form-item js-form-type-number form-item-range-output-left__output js-form-item-range-output-left__output form-no-label">'),
    );
    $assert_session->responseContains('<label for="range_output_left__output" class="visually-hidden">range_output_left</label>');
    $assert_session->responseContains('<input style="background-color: yellow;width:6em" type="number" id="range_output_left__output" step="100" min="0" max="10000" class="form-number" />');

    // Check output right range element.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<span class="field-suffix"><span class="webform-range-output-delimiter"></span><div class="js-form-item form-item form-type-number js-form-type-number form-item-range-output-disabled__output js-form-item-range-output-disabled__output form-no-label form-disabled">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<span class="field-suffix"><span class="webform-range-output-delimiter"></span><div class="js-form-item form-item js-form-type-number form-item-range-output-disabled__output js-form-item-range-output-disabled__output form-no-label form-disabled">'),
    );
    $assert_session->responseContains('<label for="range_output_right__output" class="visually-hidden">range_output_right</label>');
    $assert_session->responseContains('<input style="width:4em" type="number" id="range_output_right__output" step="1" min="0" max="100" class="form-number" />');

    // Check processing.
    $this->drupalGet('/webform/test_element_range');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("range: '50'
range_advanced: '0'
range_output_above: '50'
range_output_below: '50'
range_output_right: '50'
range_output_left: '5000'
range_output_disabled: ''");
  }

}
