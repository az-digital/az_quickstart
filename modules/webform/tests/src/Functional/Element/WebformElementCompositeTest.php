<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\webform\Entity\Webform;

/**
 * Tests for composite element (builder).
 *
 * @group webform
 */
class WebformElementCompositeTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_composite',
    'test_element_composite_wrapper',
  ];

  /**
   * Test composite (builder).
   */
  public function testComposite() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Builder.
    /* ********************************************************************** */

    $webform = Webform::load('test_element_composite');

    // Check processing for user who can't edit source.
    $this->postSubmission($webform);
    $assert_session->responseContains("webform_element_composite_basic:
  first_name:
    '#type': textfield
    '#required': true
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#required': true
    '#title': 'Last name'
webform_element_composite_advanced:
  first_name:
    '#type': textfield
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#title': 'Last name'
  sex:
    '#type': select
    '#options':
      Male: Male
      Female: Female
    '#title': Sex
  martial_status:
    '#type': webform_select_other
    '#options': marital_status
    '#title': 'Martial status'
  employment_status:
    '#type': webform_select_other
    '#options': employment_status
    '#title': 'Employment status'
  age:
    '#type': number
    '#title': Age
    '#field_suffix': ' yrs. old'
    '#min': 1
    '#max': 125");

    // Check processing for user who can edit source.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform);
    $assert_session->responseContains("webform_element_composite_basic:
  first_name:
    '#type': textfield
    '#required': true
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#required': true
    '#title': 'Last name'
webform_element_composite_advanced:
  first_name:
    '#type': textfield
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#title': 'Last name'
  sex:
    '#type': select
    '#options':
      Male: Male
      Female: Female
    '#title': Sex
  martial_status:
    '#type': webform_select_other
    '#options': marital_status
    '#title': 'Martial status'
  employment_status:
    '#type': webform_select_other
    '#options': employment_status
    '#title': 'Employment status'
  age:
    '#type': number
    '#title': Age
    '#field_suffix': ' yrs. old'
    '#min': 1
    '#max': 125");

    /* ********************************************************************** */
    // Wrapper.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_composite_wrapper');

    // Check fieldset wrapper.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-radios-wrapper-fieldset" id="edit-radios-wrapper-fieldset--wrapper" class="radios--wrapper fieldgroup form-composite webform-composite-visible-title required js-webform-type-radios webform-type-radios js-form-item form-item js-form-wrapper form-wrapper">');

    // Check fieldset wrapper with hidden title.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-radios-wrapper-fieldset-hidden-title" id="edit-radios-wrapper-fieldset-hidden-title--wrapper" class="radios--wrapper fieldgroup form-composite webform-composite-hidden-title required js-webform-type-radios webform-type-radios js-form-item form-item js-form-wrapper form-wrapper">');

    // Check form element wrapper.
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item form-type-radio js-form-type-radio form-item-radios-wrapper-form-element js-form-item-radios-wrapper-form-element">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item js-form-type-radios form-item-radios-wrapper-form-element js-form-item-radios-wrapper-form-element">'),
    );

    // Check container wrapper.
    $assert_session->responseContains('<div data-drupal-selector="edit-radios-wrapper-container" id="edit-radios-wrapper-container--wrapper" class="radios--wrapper fieldgroup form-composite js-form-wrapper form-wrapper">');

    // Check radios 'aria-describedby' with wrapper description.
    $assert_session->responseContains('<input data-drupal-selector="edit-radios-wrapper-fieldset-description-one" aria-describedby="edit-radios-wrapper-fieldset-description--wrapper--description" type="radio" id="edit-radios-wrapper-fieldset-description-one" name="radios_wrapper_fieldset_description" value="One" class="form-radio" />');
    $assert_session->responseContains('<div class="description"><div id="edit-radios-wrapper-fieldset-description--wrapper--description" data-drupal-field-elements="description" class="webform-element-description">This is a description</div>');

    // Check wrapper with #states.
    $assert_session->responseContains('<fieldset data-drupal-selector="edit-states-fieldset" class="js-webform-states-hidden radios--wrapper fieldgroup form-composite webform-composite-visible-title js-webform-type-radios webform-type-radios js-form-item form-item js-form-wrapper form-wrapper" id="edit-states-fieldset--wrapper" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-composite-wrapper-add-form :input[name=\u0022states_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-webform-states-hidden js-form-item form-item form-type-radios js-form-type-radios form-item-states-form-item js-form-item-states-form-item" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-composite-wrapper-add-form :input[name=\u0022states_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-webform-states-hidden js-form-item form-item js-form-type-radios form-item-states-form-item js-form-item-states-form-item" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-composite-wrapper-add-form :input[name=\u0022states_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">'),
    );
    $assert_session->responseContains('<div data-drupal-selector="edit-states-container" class="js-webform-states-hidden radios--wrapper fieldgroup form-composite js-form-wrapper form-wrapper" id="edit-states-container--wrapper" data-drupal-states="{&quot;visible&quot;:{&quot;.webform-submission-test-element-composite-wrapper-add-form :input[name=\u0022states_checkbox\u0022]&quot;:{&quot;checked&quot;:true}}}">');

    // Below tests are only failing on Drupal.org and pass locally.
    // Check radios 'aria-describedby' with individual descriptions.
    // phpcs:disable
    // $assert_session->responseContains('<input data-drupal-selector="edit-radios-wrapper-fieldset-element-descriptions-one" aria-describedby="edit-radios-wrapper-fieldset-element-descriptions-one--description" type="radio" id="edit-radios-wrapper-fieldset-element-descriptions-one" name="radios_wrapper_fieldset_element_descriptions" value="One" class="form-radio" />');
    // $assert_session->responseContains('<div id="edit-radios-wrapper-fieldset-element-descriptions-one--description" class="webform-element-description">This is a radio description</div>');
    // phpcs:enable
  }

}
