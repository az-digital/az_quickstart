<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for rating element.
 *
 * @group webform
 */
class WebformElementRatingTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_rating'];

  /**
   * Test rating element.
   */
  public function testRating() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_rating');

    // Check basic rating display.
    $assert_session->responseContains('<label for="edit-rating-basic">rating_basic</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-rating-basic" type="range" class="js-webform-visually-hidden form-webform-rating" id="edit-rating-basic" name="rating_basic" value="0" step="1" min="0" max="5" />');
    $assert_session->responseContains('<div class="rateit svg rateit-medium" data-rateit-min="0" data-rateit-max="5" data-rateit-step="1" data-rateit-resetable="false" data-rateit-readonly="false" data-rateit-backingfld="[data-drupal-selector=&quot;edit-rating-basic&quot;]" data-rateit-value="" data-rateit-starheight="24" data-rateit-starwidth="24">');

    // Check advanced rating display.
    $assert_session->responseContains('<label for="edit-rating-advanced">rating_advanced</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-rating-advanced" type="range" class="js-webform-visually-hidden form-webform-rating" id="edit-rating-advanced" name="rating_advanced" value="0" step="0.1" min="0" max="10" />');
    $assert_session->responseContains('<div class="rateit svg rateit-large" data-rateit-min="0" data-rateit-max="10" data-rateit-step="0.1" data-rateit-resetable="true" data-rateit-readonly="false" data-rateit-backingfld="[data-drupal-selector=&quot;edit-rating-advanced&quot;]" data-rateit-value="" data-rateit-starheight="32" data-rateit-starwidth="32">');

    // Check required rating display.
    $assert_session->responseContains('<label for="edit-rating-required" class="js-form-required form-required">rating_required</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-rating-required" type="range" class="js-webform-visually-hidden form-webform-rating required" id="edit-rating-required" name="rating_required" value="0" step="1" min="0" max="5" required="required" aria-required="true" />');
    $assert_session->responseContains('<div class="rateit svg rateit-medium" data-rateit-min="0" data-rateit-max="5" data-rateit-step="1" data-rateit-resetable="false" data-rateit-readonly="false" data-rateit-backingfld="[data-drupal-selector=&quot;edit-rating-required&quot;]" data-rateit-value="" data-rateit-starheight="24" data-rateit-starwidth="24"></div>');

    // Check processing.
    $this->drupalGet('/webform/test_element_rating');
    $edit = [
      'rating_basic' => '1',
      'rating_advanced' => '2',
      'rating_required' => '3',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("rating_basic: '1'
rating_advanced: '2'
rating_required: '3'");

    // Check required validation.
    $this->drupalGet('/webform/test_element_rating');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('rating_required field is required.');
  }

}
