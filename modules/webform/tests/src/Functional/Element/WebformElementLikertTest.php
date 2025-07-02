<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for likert element.
 *
 * @group webform
 */
class WebformElementLikertTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_likert'];

  /**
   * Test likert element.
   */
  public function testLikertElement() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_likert');

    // Check default likert element.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.3',
      currentCallable: fn() => $assert_session->responseContains('<table class="webform-likert-table sticky-header responsive-enabled" data-likert-answers-count="3" data-drupal-selector="edit-likert-default-table" id="edit-likert-default-table" data-striping="1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<table class="webform-likert-table sticky-enabled responsive-enabled" data-likert-answers-count="3" data-drupal-selector="edit-likert-default-table" id="edit-likert-default-table" data-striping="1">'),
    );
    $assert_session->responseMatches('#<tr>\s+<th><span class="visually-hidden">Questions</span></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+</tr>#');
    $assert_session->responseContains('<label>Question 1</label>');

    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item form-type-radio js-form-type-radio form-item-likert-default-q1 js-form-item-likert-default-q1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item js-form-type-radio form-item-likert-default-q1 js-form-item-likert-default-q1">'),
    );
    $assert_session->responseContains('<input aria-labelledby="edit-likert-default-table-q1-likert-question" data-drupal-selector="edit-likert-default-q1" type="radio" id="edit-likert-default-q1" name="likert_default[q1]" value="1" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-likert-default-q1" class="option"><span class="webform-likert-label visually-hidden">Option 1</span></label>');

    // Check advanced likert element with N/A.
    $assert_session->responseMatches('#<tr>\s+<th><span class="visually-hidden">Questions</span></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+<th>Not applicable</th>\s+</tr>#');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item form-type-radio js-form-type-radio form-item-likert-advanced-q1 js-form-item-likert-advanced-q1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item js-form-type-radio form-item-likert-advanced-q1 js-form-item-likert-advanced-q1">'),
    );
    $assert_session->responseContains('<input aria-labelledby="edit-likert-advanced-table-q1-likert-question" required="required" data-drupal-selector="edit-likert-advanced-q1" type="radio" id="edit-likert-advanced-q1--4" name="likert_advanced[q1]" value="N/A" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-likert-advanced-q1--4" class="option"><span class="webform-likert-label visually-hidden">Not applicable</span></label>');

    // Check likert with description.
    $assert_session->responseContains('<th>Option 1<div class="description">This is a description</div>');
    $assert_session->responseContains('<label>Question 1</label>');
    $assert_session->responseContains('<div id="edit-likert-description-table-q1-likert-question--description" class="webform-element-description">');
    $assert_session->responseContains('<label for="edit-likert-description-q1" class="option"><span class="webform-likert-label visually-hidden">Option 1</span></label>');
    $assert_session->responseContains('<span class="webform-likert-description hidden">This is a description</span>');

    // Check likert with help.
    $assert_session->responseContains('<th>Option 1<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Option 1" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Option 1&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $assert_session->responseContains('<label>Question 1<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Question 1" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Question 1&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $assert_session->responseContains('<label for="edit-likert-help-q1--2" class="option"><span class="webform-likert-label visually-hidden">Option 2<span class="webform-likert-help hidden"><span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="Option 2" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;Option 2&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check likert with custom required error.
    $this->drupalGet('/webform/test_element_likert');
    $this->submitForm([], 'Submit');
    $assert_session->responseNotContains('Question 1 field is required.');
    $assert_session->responseNotContains('Question 2 field is required.');
    $assert_session->responseNotContains('Question 3 field is required.');
    $assert_session->responseContains('{custom error for Question 1}');
    $assert_session->responseContains('{custom error for Question 2}');
    $assert_session->responseContains('{custom error for Question 3}');

    // Check likert with HTMl required error.
    $this->drupalGet('/webform/test_element_likert');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('Question <strong>1</strong> field is required.');
    $assert_session->responseContains('Question <strong>2</strong> field is required.');
    $assert_session->responseContains('Question <strong>3</strong> field is required.');

    // Check likert processing.
    $this->drupalGet('/webform/test_element_likert');
    $edit = [
      'likert_advanced[q1]' => '1',
      'likert_advanced[q2]' => '2',
      'likert_advanced[q3]' => 'N/A',
      'likert_values[0]' => '0',
      'likert_values[1]' => '1',
      'likert_values[2]' => 'N/A',
      'likert_html[q1]' => '1',
      'likert_html[q2]' => '1',
      'likert_html[q3]' => '1',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("likert_default:
  q1: null
  q2: null
  q3: null
likert_advanced:
  q1: '1'
  q2: '2'
  q3: N/A
likert_description:
  q1: null
  q2: null
  q3: null
likert_help:
  q1: null
  q2: null
  q3: null
likert_html:
  q1: '1'
  q2: '1'
  q3: '1'
likert_values:
  - '0'
  - '1'
  - N/A
likert_trigger_required: 0
likert_states_required:
  q1: null
  q2: null
  q3: null");
  }

}
