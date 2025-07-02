<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform height element.
 *
 * @group webform
 */
class WebformElementHeightTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_height'];

  /**
   * Test height element.
   */
  public function testheightElement() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_height');

    $this->drupalGet('/webform/test_element_height');

    // Check height_number_text.
    $assert_session->responseContains('<input data-drupal-selector="edit-height-number-text-feet" type="number" id="edit-height-number-text-feet" name="height_number_text[feet]" value="5" step="1" min="0" max="8" class="form-number" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-height-number-text-inches" type="number" id="edit-height-number-text-inches" name="height_number_text[inches]" value="0" step="1" min="0" max="11" class="form-number" />');

    // Check height_select_text.
    $assert_session->responseContains('<select data-drupal-selector="edit-height-select-text-feet" id="edit-height-select-text-feet" name="height_select_text[feet]" class="form-select"><option value=""></option><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3" selected="selected">3</option><option value="4">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option></select>');
    $assert_session->responseContains('<select data-drupal-selector="edit-height-select-text-inches" id="edit-height-select-text-inches" name="height_select_text[inches]" class="form-select"><option value=""></option><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4" selected="selected">4</option><option value="5">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option><option value="9">9</option><option value="10">10</option><option value="11">11</option></select>');

    // Check height_number_step.
    $assert_session->responseContains('<select data-drupal-selector="edit-height-number-step-feet" id="edit-height-number-step-feet" name="height_number_step[feet]" class="form-select"><option value=""></option><option value="0">0</option><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option><option value="5" selected="selected">5</option><option value="6">6</option><option value="7">7</option><option value="8">8</option></select>');
    $assert_session->responseContains('<select data-drupal-selector="edit-height-number-step-inches" id="edit-height-number-step-inches" name="height_number_step[inches]" class="form-select"><option value=""></option><option value="0.0">0.0</option><option value="0.5" selected="selected">0.5</option><option value="1.0">1.0</option><option value="1.5">1.5</option><option value="2.0">2.0</option><option value="2.5">2.5</option><option value="3.0">3.0</option><option value="3.5">3.5</option><option value="4.0">4.0</option><option value="4.5">4.5</option><option value="5.0">5.0</option><option value="5.5">5.5</option><option value="6.0">6.0</option><option value="6.5">6.5</option><option value="7.0">7.0</option><option value="7.5">7.5</option><option value="8.0">8.0</option><option value="8.5">8.5</option><option value="9.0">9.0</option><option value="9.5">9.5</option><option value="10.0">10.0</option><option value="10.5">10.5</option><option value="11.0">11.0</option></select>');

    // Post a submission.
    $edit = [
      'height_number_empty_required[feet]' => '5',
      'height_number_empty_required[inches]' => '5',
      'height_select_empty_required[feet]' => '5',
      'height_select_empty_required[inches]' => '5',
    ];
    $this->postSubmission($webform, $edit);

    // Check submission data.
    $assert_session->responseContains("height_number_text: '60'
height_number_symbol_required: '50'
height_select_text: '40'
height_select_text_abbreviate: '30'
height_select_symbol_required: '20'
height_select_suffix_symbol_required: '10'
height_select_suffix_text: '0'
height_select_min_max: '120'
height_number_step: '60.5'
height_number_empty: ''
height_select_empty: ''
height_number_empty_required: '65'
height_select_empty_required: '65'");

    // Check submission display.
    $assert_session->responseMatches('#<label>height_number_text</label>\s+5 feet\s+</div>#s');
    $assert_session->responseMatches('#<label>height_number_symbol_required</label>\s+4″2′\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_text</label>\s+3 feet 4 inches\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_text_abbreviate</label>\s+2 ft 6 in\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_symbol_required</label>\s+1″8′\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_suffix_symbol_required</label>\s+10′\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_min_max</label>\s+10 feet\s+</div>#s');
    $assert_session->responseMatches('#<label>height_number_step</label>\s+5 feet 0.5 inches\s+</div>#s');
    $assert_session->responseMatches('#<label>height_number_empty_required</label>\s+5 feet 5 inches\s+</div>#s');
    $assert_session->responseMatches('#<label>height_select_empty_required</label>\s+5 feet 5 inches\s+</div>#s');

    $assert_session->responseNotContains('<label>height_select_suffix_text</label>');
    $assert_session->responseNotContains('<label>height_number_empty</label>');
    $assert_session->responseNotContains('<label>height_select_empty</label>');
  }

}
