<?php

namespace Drupal\Tests\webform_jqueryui_buttons\Functional;

use Drupal\Tests\webform\Functional\Element\WebformElementBrowserTestBase;

/**
 * Tests for webform element buttons.
 *
 * @group webform_jqueryui_buttons
 */
class WebformElementButtonsTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_jqueryui_buttons', 'webform_jqueryui_buttons_test'];

  /**
   * Tests buttons elements.
   */
  public function testBuildingOtherElements() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_buttons');

    // Check basic buttons_other.
    $assert_session->responseContains('<span class="fieldset-legend">buttons_other_basic</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-buttons-other-basic-buttons-one" type="radio" id="edit-buttons-other-basic-buttons-one" name="buttons_other_basic[buttons]" value="One" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-buttons-other-basic-buttons-one" class="option">One</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-buttons-other-basic-other" type="text" id="edit-buttons-other-basic-other" name="buttons_other_basic[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check advanced buttons_other w/ custom label.
    $assert_session->responseContains('<span class="fieldset-legend js-form-required form-required">buttons_other_advanced</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-buttons-other-advanced-buttons-one" type="radio" id="edit-buttons-other-advanced-buttons-one" name="buttons_other_advanced[buttons]" value="One" class="form-radio" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-buttons-other-advanced-other" aria-describedby="edit-buttons-other-advanced-other--description" type="text" id="edit-buttons-other-advanced-other" name="buttons_other_advanced[other]" value="Four" size="60" maxlength="255" placeholder="What is this other option" class="form-text" />');
    $assert_session->responseContains('<div id="edit-buttons-other-advanced-other--description" class="webform-element-description">Other button description</div>');

    // Check buttons other required when checked.
    $this->drupalGet('/webform/test_element_buttons');
    $edit = [
      'buttons_other_basic[buttons]' => '_other_',
      'buttons_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('buttons_other_basic field is required.');

    // Check buttons other not required when not checked.
    $edit = [
      'buttons_other_basic[buttons]' => 'One',
      'buttons_other_basic[other]' => '',
    ];
    $this->drupalGet('/webform/test_element_buttons');
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('buttons_other_basic field is required.');

    // Check buttons other required validation.
    $this->drupalGet('/webform/test_element_buttons');
    $edit = [
      'buttons_other_advanced[buttons]' => '_other_',
      'buttons_other_advanced[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('buttons_other_advanced field is required.');

    // Check buttons other processing w/ other.
    $this->drupalGet('/webform/test_element_buttons');
    $edit = [
      'buttons_other_advanced[buttons]' => '_other_',
      'buttons_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('buttons_other_advanced: Five');

    // Check buttons other processing w/o other.
    $this->drupalGet('/webform/test_element_buttons');
    $edit = [
      'buttons_other_advanced[buttons]' => 'One',
      // This value is ignored, because 'buttons_other_advanced[buttons]' is not set to '_other_'.
      'buttons_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('buttons_other_advanced: One');
    $assert_session->responseNotContains('buttons_other_advanced: Five');
  }

}
