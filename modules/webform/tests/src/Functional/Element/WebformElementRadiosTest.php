<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for webform element radios.
 *
 * @group webform
 */
class WebformElementRadiosTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_radios'];

  /**
   * Tests radios element.
   */
  public function testElementRadios() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $this->drupalGet('/webform/test_element_radios');

    // Check radios with description display.
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-description-one" aria-describedby="edit-radios-description-one--description" type="radio" id="edit-radios-description-one" name="radios_description" value="one" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-radios-description-one" class="option">One</label>');
    $assert_session->responseContains('<div id="edit-radios-description-one--description" class="webform-element-description">This is a description</div>');

    // Check radios with help text display.
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-help-one" type="radio" id="edit-radios-help-one" name="radios_help" value="one" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-radios-help-one" class="option">One<span class="webform-element-help js-webform-element-help" role="tooltip" tabindex="0" aria-label="One" data-webform-help="&lt;div class=&quot;webform-element-help--title&quot;&gt;One&lt;/div&gt;&lt;div class=&quot;webform-element-help--content&quot;&gt;This is a description&lt;/div&gt;"><span aria-hidden="true">?</span></span>');

    // Check radios displayed as buttons.
    $assert_session->responseContains('<div id="edit-radios-buttons" class="js-webform-radios webform-options-display-buttons"><div class="webform-options-display-buttons-wrapper">');
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-buttons-yes" class="visually-hidden form-radio" type="radio" id="edit-radios-buttons-yes" name="radios_buttons" value="Yes" />');
    $assert_session->responseContains('<label class="webform-options-display-buttons-label option" for="edit-radios-buttons-yes">Yes</label>');

    // Check radios displayed as buttons_horizontal.
    $assert_session->responseContains('<div id="edit-radios-buttons-horizontal" class="js-webform-radios webform-options-display-buttons webform-options-display-buttons-horizontal"><div class="webform-options-display-buttons-wrapper">');

    // Check radios displayed as buttons_vertical.
    $assert_session->responseContains('<div id="edit-radios-buttons-vertical" class="js-webform-radios webform-options-display-buttons webform-options-display-buttons-vertical"><div class="webform-options-display-buttons-wrapper">');

    // Check radios displayed as buttons with description.
    $assert_session->responseContains('<label class="webform-options-display-buttons-label option" for="edit-radios-buttons-description-one"><div class="webform-options-display-buttons-title">One</div><div class="webform-options-display-buttons-description description">This is a description</div></label>');

    // Check options (custom) properties wrapper attributes.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div data-custom="custom wrapper data" style="border: red 1px solid" class="one-custom-wrapper-class js-form-item form-item form-type-radio js-form-type-radio form-item-radios-options-properties js-form-item-radios-options-properties">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div data-custom="custom wrapper data" style="border: red 1px solid" class="one-custom-wrapper-class js-form-item form-item js-form-type-radio form-item-radios-options-properties js-form-item-radios-options-properties">'),
    );
    // Check options (custom) properties label attributes.
    $assert_session->responseContains('<label data-custom="custom label data" style="border: blue 1px solid" class="one-custom-label-class option" for="edit-radios-options-properties-two">Two</label>');

    // Check options (custom) properties attributes.
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-options-properties-two" data-custom="custom input data" style="border: yellow 1px solid" class="one-custom-class form-radio" aria-describedby="edit-radios-options-properties-two--description" type="radio" id="edit-radios-options-properties-two" name="radios_options_properties" value="two" />');

    // Check other options (custom) properties attributes.
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-other-options-properties-radios-one" disabled="disabled" type="radio" id="edit-radios-other-options-properties-radios-one" name="radios_other_options_properties[radios]" value="one" class="form-radio" />');

    // Check radios results does not include description.
    $this->drupalGet('/webform/test_element_radios');
    $edit = [
      'radios_required' => 'Yes',
      'radios_required_conditional_trigger' => FALSE,
      'buttons_required_conditional_trigger' => FALSE,
      'radios_description' => 'one',
      'radios_help' => 'two',
    ];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseMatches('#<label>radios_description</label>\s+One#');
    $assert_session->responseMatches('#<label>radios_help</label>\s+Two#');
  }

}
