<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for fieldset element.
 *
 * @group webform
 */
class WebformElementFieldsetTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_fieldset'];

  /**
   * Test fieldset element.
   */
  public function testFieldset() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_fieldset');

    // Check fieldset with help, field prefix, field suffix, description,
    // and more. Also, check that invalid 'required' and 'aria-required'
    // attributes are removed.
    $assert_session->responseContains('<fieldset class="webform-has-field-prefix webform-has-field-suffix required webform-element-help-container--title webform-element-help-container--title-after js-webform-type-fieldset webform-type-fieldset js-form-item form-item js-form-wrapper form-wrapper" data-drupal-selector="edit-fieldset" aria-describedby="edit-fieldset--description" id="edit-fieldset">');
    $assert_session->responseContains('<span class="fieldset-legend js-form-required form-required">fieldset<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="fieldset" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;fieldset&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is help text.&lt;/div&gt;"><span aria-hidden="true">?</span></span>');
    $assert_session->responseContains('<span class="field-prefix">prefix</span>');
    $assert_session->responseContains('<span class="field-suffix">suffix</span>');
    $assert_session->responseContains('<div class="description">');
    $assert_session->responseContains('<div id="edit-fieldset--description" data-drupal-field-elements="description" class="webform-element-description">This is a description.</div>');
    $assert_session->responseContains('<div id="edit-fieldset--more" class="js-webform-element-more webform-element-more">');

    // Check fieldset title_display: invisible.
    $assert_session->responseContains('<span class="visually-hidden fieldset-legend">fieldset_title_invisible</span>');

    // Check fieldset title_display: none.
    $assert_session->responseContains('<legend style="display:none">');
    $assert_session->responseContains('<span class="fieldset-legend"></span>');

    // Check fieldset description_display: before.
    $assert_session->responseContains('<div class="description"><div id="edit-fieldset-description-before--description" data-drupal-field-elements="description" class="webform-element-description">This is a description before.</div>');
  }

}
