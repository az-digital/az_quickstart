<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for scale element.
 *
 * @group webform
 */
class WebformElementScaleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_scale'];

  /**
   * Test scale element.
   */
  public function testRating() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_scale');

    // Check basic scale element.
    $assert_session->responseContains('<div class="webform-scale webform-scale-circle webform-scale-medium webform-scale-1-to-5">');
    $assert_session->responseContains('<input data-drupal-selector="edit-scale-1" class="webform-scale-1 visually-hidden form-radio" type="radio" id="edit-scale-1" name="scale" value="1" />');

    // Check scale with text element.
    $assert_session->responseContains('<div class="webform-scale webform-scale-circle webform-scale-medium webform-scale-0-to-10">');
    $assert_session->responseContains('<input data-drupal-selector="edit-scale-text-0" class="webform-scale-0 visually-hidden form-radio" type="radio" id="edit-scale-text-0" name="scale_text" value="0" />');
    $assert_session->responseContains('<div class="webform-scale-text webform-scale-text-below"><div class="webform-scale-text-min">0 = disagree</div><div class="webform-scale-text-max">agree = 10</div></div></div></div>');

    // Check processing.
    $this->drupalGet('/webform/test_element_scale');
    $edit = [
      'scale' => '1',
      'scale_required' => '1',
      'scale_text' => '2',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("scale: '1'
scale_required: '1'
scale_text: '2'
scale_text_above: null
scale_small: null
scale_medium: null
scale_large: null
scale_square: null
scale_flexbox: null");
  }

}
