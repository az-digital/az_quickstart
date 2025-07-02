<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element other.
 *
 * @group webform
 */
class WebformElementOtherTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_other'];

  /**
   * Tests options with other elements.
   */
  public function testBuildingOtherElements() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_other');

    /* ********************************************************************** */
    // select_other.
    /* ********************************************************************** */

    // Check basic select_other.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-select-other-basic" class="js-webform-select-other webform-select-other webform-select-other--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-webform-select-other webform-type-webform-select-other js-form-item form-item js-form-wrapper form-wrapper" id="edit-select-other-basic">');
    $assert_session->responseContains('<span class="fieldset-legend">Select other basic</span>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-other-basic-select" id="edit-select-other-basic-select" name="select_other_basic[select]" class="form-select">');
    $assert_session->responseContains('<input data-drupal-selector="edit-select-other-basic-other" type="text" id="edit-select-other-basic-other" name="select_other_basic[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');
    $assert_session->responseContains('<option value="_other_" selected="selected">Other…</option>');

    // Check advanced select_other w/ custom label.
    $assert_session->responseContains('<span class="fieldset-legend js-form-required form-required">Select other advanced</span>');
    $assert_session->responseContains('<select data-webform-required-error="This is a custom required error message." data-drupal-selector="edit-select-other-advanced-select" id="edit-select-other-advanced-select" name="select_other_advanced[select]" class="form-select required" required="required" aria-required="true">');
    $assert_session->responseContains('<option value="_other_" selected="selected">Is there another option you wish to enter?</option>');
    $assert_session->responseContains('<label for="edit-select-other-advanced-other">Other</label>');
    $assert_session->responseContains('<input data-webform-required-error="This is a custom required error message." data-counter-type="character" data-counter-minimum="4" data-counter-maximum="10" class="js-webform-counter webform-counter form-text" minlength="4" data-drupal-selector="edit-select-other-advanced-other" aria-describedby="edit-select-other-advanced-other--description" type="text" id="edit-select-other-advanced-other" name="select_other_advanced[other]" value="Four" size="20" maxlength="10" placeholder="What is this other option" />');
    $assert_session->responseContains('<div id="edit-select-other-advanced-other--description" class="webform-element-description">Other select description</div>');

    // Check multiple select_other.
    $assert_session->responseContains('<span class="fieldset-legend">Select other multiple</span>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-other-multiple-select" multiple="multiple" name="select_other_multiple[select][]" id="edit-select-other-multiple-select" class="form-select">');
    $assert_session->responseContains('<input data-drupal-selector="edit-select-other-multiple-other" type="text" id="edit-select-other-multiple-other" name="select_other_multiple[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check select_other with zero (0) as the default value.
    $assert_session->responseContains('<span class="fieldset-legend">Select other zero</span>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-other-zero-select" id="edit-select-other-zero-select" name="select_other_zero[select]" class="form-select">');
    $assert_session->responseContains('<input data-drupal-selector="edit-select-other-zero-other" type="text" id="edit-select-other-zero-other" name="select_other_zero[other]" value="0" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    /* ********************************************************************** */
    // checkboxes_other.
    /* ********************************************************************** */

    // Check basic checkboxes.
    $assert_session->responseContains('<span class="fieldset-legend">Checkboxes other basic</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-checkboxes-other-basic-checkboxes-other-" type="checkbox" id="edit-checkboxes-other-basic-checkboxes-other-" name="checkboxes_other_basic[checkboxes][_other_]" value="_other_" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('<label for="edit-checkboxes-other-basic-checkboxes-other-" class="option">Other…</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-checkboxes-other-basic-other" type="text" id="edit-checkboxes-other-basic-other" name="checkboxes_other_basic[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check advanced checkboxes.
    $assert_session->responseContains('<div id="edit-checkboxes-other-advanced-checkboxes" class="js-webform-checkboxes webform-options-display-two-columns form-checkboxes">');
    $assert_session->responseContains('<span class="fieldset-legend js-form-required form-required">Checkboxes other advanced</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-checkboxes-other-advanced-other" aria-describedby="edit-checkboxes-other-advanced-other--description" type="text" id="edit-checkboxes-other-advanced-other" name="checkboxes_other_advanced[other]" value="Four" size="60" maxlength="255" placeholder="What is this other option" class="form-text" />');
    $assert_session->responseContains('<div id="edit-checkboxes-other-advanced-other--description" class="webform-element-description">Other checkbox description</div>');
    $assert_session->responseContains('<label for="edit-checkboxes-other-advanced-checkboxes-one" class="option">One<span class="webform-element-help js-webform-element-help"');

    /* ********************************************************************** */
    // radios_other.
    /* ********************************************************************** */

    // Check basic radios_other.
    $assert_session->responseContains('<span class="fieldset-legend">Radios other basic</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-other-basic-radios-other-" type="radio" id="edit-radios-other-basic-radios-other-" name="radios_other_basic[radios]" value="_other_" checked="checked" class="form-radio" />');
    $assert_session->responseContains('<label for="edit-radios-other-basic-radios-other-" class="option">Other…</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-other-basic-other" type="text" id="edit-radios-other-basic-other" name="radios_other_basic[other]" value="Four" size="60" maxlength="255" placeholder="Enter other…" class="form-text" />');

    // Check advanced radios_other w/ custom label.
    $assert_session->responseContains('<span class="fieldset-legend js-form-required form-required">Radios other advanced</span>');
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-other-advanced-radios-other-" type="radio" id="edit-radios-other-advanced-radios-other-" name="radios_other_advanced[radios]" value="_other_" checked="checked" class="form-radio" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-other-advanced-other" aria-describedby="edit-radios-other-advanced-other--description" type="text" id="edit-radios-other-advanced-other" name="radios_other_advanced[other]" value="Four" size="60" maxlength="255" placeholder="What is this other option" class="form-text" />');
    $assert_session->responseContains('<div id="edit-radios-other-advanced-other--description" class="webform-element-description">Other radio description</div>');
    $assert_session->responseContains('<label for="edit-radios-other-advanced-radios-one" class="option">One<span class="webform-element-help js-webform-element-help"');

    /* ********************************************************************** */
    // wrapper_type.
    /* ********************************************************************** */

    // Check form_item wrapper type.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-webform-select-other webform-select-other js-form-item form-item form-type-webform-select-other js-form-type-webform-select-other form-item-wrapper-other-form-element js-form-item-wrapper-other-form-element" id="edit-wrapper-other-form-element">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-webform-select-other webform-select-other js-form-item form-item js-form-type-webform-select-other form-item-wrapper-other-form-element js-form-item-wrapper-other-form-element" id="edit-wrapper-other-form-element">'),
    );
    // Check container wrapper type.
    $assert_session->responseContains('<div data-drupal-selector="edit-wrapper-other-container" class="js-webform-select-other webform-select-other webform-select-other--wrapper fieldgroup form-composite js-form-wrapper form-wrapper" id="edit-wrapper-other-container">');
  }

  /**
   * Tests value processing for other elements.
   */
  public function testProcessingOtherElements() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_other');

    /* ********************************************************************** */
    // Basic input processing.
    /* ********************************************************************** */

    $this->postSubmission($webform);
    $assert_session->responseContains("select_other_basic: Four
select_other_advanced: Four
select_other_multiple:
  - One
  - Two
  - Four
select_other_zero: '0'
checkboxes_other_basic:
  - One
  - Two
  - Four
checkboxes_other_advanced:
  - One
  - Two
  - Four
checkboxes_other_indexed:
  - '0'
  - '1'
  - '2'
  - '3'
radios_other_basic: Four
radios_other_advanced: Four
wrapper_other_fieldset: ''
wrapper_other_form_element: ''
wrapper_other_container: ''");

    /* ********************************************************************** */
    // select_other.
    /* ********************************************************************** */

    // Check select other is required when selected.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_basic[select]' => '_other_',
      'select_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Select other basic field is required.');

    // Check select other is not required when not selected.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_basic[select]' => '',
      'select_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Select other basic field is required.');

    // Check select other required validation.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_advanced[select]' => '',
      'select_other_advanced[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Select other advanced field is required.');
    $assert_session->responseContains('This is a custom required error message.');

    // Check select other custom required error.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_advanced[select]' => '_other_',
      'select_other_advanced[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Select other advanced field is required.');
    $assert_session->responseContains('This is a custom required error message.');

    // Check select other processing w/ other min/max character validation.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_advanced[select]' => '_other_',
      'select_other_advanced[other]' => 'X',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Other must be at least <em class="placeholder">4</em> characters but is currently <em class="placeholder">1</em> characters long.');

    // Check select other processing w/ other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_advanced[select]' => '_other_',
      'select_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('select_other_advanced: Five');

    // Check select other processing w/o other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'select_other_advanced[select]' => 'One',
      // This value is ignored, because 'select_other_advanced[select]' is not set to '_other_'.
      'select_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('select_other_advanced: One');
    $assert_session->responseNotContains('select_other_advanced: Five');

    // Check select other validation is required when default value is NULL.
    $elements = $webform->getElementsDecoded();
    $elements['select_other']['select_other_advanced']['#default_value'] = NULL;
    $webform->setElements($elements)->save();
    $this->drupalGet('/webform/test_element_other');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('This is a custom required error message.');

    // Check select other validation is skipped when #access is set to FALSE.
    $elements['select_other']['select_other_advanced']['#access'] = FALSE;
    $webform->setElements($elements)->save();
    $this->drupalGet('/webform/test_element_other');
    $this->submitForm([], 'Submit');
    $assert_session->responseNotContains('This is a custom required error message.');

    /* ********************************************************************** */
    // radios_other.
    /* ********************************************************************** */

    // Check radios other required when checked.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'radios_other_basic[radios]' => '_other_',
      'radios_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Radios other basic field is required.');

    // Check radios other not required when not checked.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'radios_other_basic[radios]' => 'One',
      'radios_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Radios other basic field is required.');

    // Check radios other required validation.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'radios_other_advanced[radios]' => '_other_',
      'radios_other_advanced[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Radios other advanced field is required.');

    // Check radios other processing w/ other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'radios_other_advanced[radios]' => '_other_',
      'radios_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('radios_other_advanced: Five');

    // Check radios other processing w/o other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'radios_other_advanced[radios]' => 'One',
      // This value is ignored, because 'radios_other_advanced[radios]' is not set to '_other_'.
      'radios_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('radios_other_advanced: One');
    $assert_session->responseNotContains('radios_other_advanced: Five');

    /* ********************************************************************** */
    // checkboxes_other.
    /* ********************************************************************** */

    // Check checkboxes other required when checked.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'checkboxes_other_basic[checkboxes][_other_]' => TRUE,
      'checkboxes_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Checkboxes other basic field is required.');

    // Check checkboxes other not required when not checked.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'checkboxes_other_basic[checkboxes][_other_]' => FALSE,
      'checkboxes_other_basic[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('Checkboxes other basic field is required.');

    // Check checkboxes other required validation.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'checkboxes_other_advanced[checkboxes][One]' => FALSE,
      'checkboxes_other_advanced[checkboxes][Two]' => FALSE,
      'checkboxes_other_advanced[checkboxes][Three]' => FALSE,
      'checkboxes_other_advanced[checkboxes][_other_]' => TRUE,
      'checkboxes_other_advanced[other]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('Checkboxes other advanced field is required.');

    // Check checkboxes other processing w/ other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'checkboxes_other_advanced[checkboxes][One]' => FALSE,
      'checkboxes_other_advanced[checkboxes][Two]' => FALSE,
      'checkboxes_other_advanced[checkboxes][Three]' => FALSE,
      'checkboxes_other_advanced[checkboxes][_other_]' => TRUE,
      'checkboxes_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('checkboxes_other_advanced:
  - Five');

    // Check checkboxes other processing w/o other.
    $this->drupalGet('/webform/test_element_other');
    $edit = [
      'checkboxes_other_advanced[checkboxes][One]' => TRUE,
      'checkboxes_other_advanced[checkboxes][Two]' => FALSE,
      'checkboxes_other_advanced[checkboxes][Three]' => FALSE,
      'checkboxes_other_advanced[checkboxes][_other_]' => FALSE,
      // This value is ignored, because 'radios_other_advanced[radios]' is not set to '_other_'.
      'checkboxes_other_advanced[other]' => 'Five',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('checkboxes_other_advanced:
  - One');
    $assert_session->responseNotContains('checkboxes_other_advanced:
  - Five');
  }

}
