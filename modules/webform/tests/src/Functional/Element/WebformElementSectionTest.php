<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for element section.
 *
 * @group webform
 */
class WebformElementSectionTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_section'];

  /**
   * Test element section.
   */
  public function testSection() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_section');

    // Check section element.
    $assert_session->responseContains('<section data-drupal-selector="edit-webform-section" aria-describedby="edit-webform-section--description" id="edit-webform-section" class="required webform-element-help-container--title webform-element-help-container--title-after js-form-item form-item js-form-wrapper form-wrapper webform-section" required="required" aria-required="true">');
    $assert_session->responseContains('<h2 class="webform-section-title js-form-required form-required">webform_section<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="webform_section" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;webform_section&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text.&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $assert_session->responseContains('<div class="description"><div id="edit-webform-section--description" class="webform-element-description">This is a description.</div>');
    $assert_session->responseContains('<div id="edit-webform-section--more" class="js-webform-element-more webform-element-more">');

    // Check custom h5 title tag.
    $assert_session->responseContains('<section data-drupal-selector="edit-webform-section-title-custom" id="edit-webform-section-title-custom" class="js-form-item form-item js-form-wrapper form-wrapper webform-section">');
    $assert_session->responseContains('<h5 style="color: red" class="webform-section-title">webform_section_title_custom</h5>');

    // Check section title_display: invisible.
    $assert_session->responseContains('<h2 class="visually-hidden webform-section-title">webform_section_title_invisible</h2>');

    // Check section description_display: default.
    $assert_session->responseMatches('/Display default description.+name="webform_section_description_display_default_textfield"/ms');

    // Check section description_display: before.
    $assert_session->responseMatches('/Display before description.+name="webform_section_description_display_before_textfield"/ms');

    // Check section description_display: after.
    $assert_session->responseMatches('/name="webform_section_description_display_after_textfield".+Display after description/ms');

    // Check section description_display: invisible.
    $assert_session->responseContains('<div class="description"><div id="edit-webform-section-description-display-invisible--description" class="webform-element-description visually-hidden">Display invisible description.</div>');

    // Check change default title tag.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_section_title_tag', 'address')
      ->save();

    $this->drupalGet('/webform/test_element_section');
    $assert_session->responseNotContains('<h2 class="webform-section-title js-form-required form-required">');
    $assert_session->responseContains('<address class="webform-section-title js-form-required form-required">');
  }

}
