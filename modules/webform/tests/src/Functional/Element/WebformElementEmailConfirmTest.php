<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;

/**
 * Tests for email_confirm element.
 *
 * @group webform
 */
class WebformElementEmailConfirmTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_email_confirm'];

  /**
   * Test email_confirm element.
   */
  public function testEmailConfirm() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_email_confirm');

    // Check basic email confirm.
    $assert_session->responseContains('<fieldset id="edit-email-confirm-basic" class="webform-email-confirm--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-email-confirm webform-type-webform-email-confirm js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseContains('<span class="visually-hidden fieldset-legend">email_confirm_basic</span>');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-basic-mail-1 js-form-item-email-confirm-basic-mail-1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item js-form-type-email form-item-email-confirm-basic-mail-1 js-form-item-email-confirm-basic-mail-1">'),
    );
    $assert_session->responseContains('<label for="edit-email-confirm-basic-mail-1">email_confirm_basic</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-email-confirm-basic-mail-1" class="webform-email form-email" type="email" id="edit-email-confirm-basic-mail-1" name="email_confirm_basic[mail_1]" value="" size="60" maxlength="254" />');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-basic-mail-2 js-form-item-email-confirm-basic-mail-2">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item js-form-type-email form-item-email-confirm-basic-mail-2 js-form-item-email-confirm-basic-mail-2">'),
    );
    $assert_session->responseContains('<label for="edit-email-confirm-basic-mail-2">Confirm email</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-email-confirm-basic-mail-2" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-basic-mail-2" name="email_confirm_basic[mail_2]" value="" size="60" maxlength="254" />');

    // Check advanced email confirm w/ custom label.
    $assert_session->responseContains('<fieldset id="edit-email-confirm-advanced" class="webform-email-confirm--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-email-confirm webform-type-webform-email-confirm js-form-item form-item js-form-wrapper form-wrapper">');
    $assert_session->responseContains('<span class="visually-hidden fieldset-legend">Email address</span>');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-advanced-mail-1 js-form-item-email-confirm-advanced-mail-1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item js-form-type-email form-item-email-confirm-advanced-mail-1 js-form-item-email-confirm-advanced-mail-1">'),
    );
    $assert_session->responseContains('<label for="edit-email-confirm-advanced-mail-1">Email address</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-email-confirm-advanced-mail-1" aria-describedby="edit-email-confirm-advanced-mail-1--description" class="webform-email form-email" type="email" id="edit-email-confirm-advanced-mail-1" name="email_confirm_advanced[mail_1]" value="" size="60" maxlength="254" placeholder="Enter email address" />');
    $assert_session->responseContains('<div id="edit-email-confirm-advanced-mail-1--description" class="webform-element-description">Please make sure to review your email address</div>');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-advanced-mail-2 js-form-item-email-confirm-advanced-mail-2">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="js-form-item form-item js-form-type-email form-item-email-confirm-advanced-mail-2 js-form-item-email-confirm-advanced-mail-2">'),
    );
    $assert_session->responseContains('<label for="edit-email-confirm-advanced-mail-2">Please confirm your email address</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-email-confirm-advanced-mail-2" aria-describedby="edit-email-confirm-advanced-mail-2--description" class="webform-email-confirm form-email" type="email" id="edit-email-confirm-advanced-mail-2" name="email_confirm_advanced[mail_2]" value="" size="60" maxlength="254" placeholder="Enter confirmation email address" />');
    $assert_session->responseContains('<div id="edit-email-confirm-advanced-mail-2--description" class="webform-element-description">Please make sure to review your confirmation email address</div>');

    // Check flexbox.
    $assert_session->responseContains('<div data-drupal-selector="edit-email-confirm-flexbox-flexbox" class="webform-flexbox js-webform-flexbox js-form-wrapper form-wrapper" id="edit-email-confirm-flexbox-flexbox"><div class="webform-flex webform-flex--1"><div class="webform-flex--container">');

    // Check inline title.
    $assert_session->responseContains('<fieldset id="edit-email-confirm-inline" class="webform-email-confirm--wrapper fieldgroup form-composite webform-composite-hidden-title js-webform-type-webform-email-confirm webform-type-webform-email-confirm js-form-item form-item js-form-wrapper form-wrapper">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="webform-element--title-inline js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-inline-mail-1 js-form-item-email-confirm-inline-mail-1">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="webform-element--title-inline js-form-item form-item js-form-type-email form-item-email-confirm-inline-mail-1 js-form-item-email-confirm-inline-mail-1">'),
    );
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<div class="webform-element--title-inline js-form-item form-item form-type-email js-form-type-email form-item-email-confirm-inline-mail-2 js-form-item-email-confirm-inline-mail-2">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<div class="webform-element--title-inline js-form-item form-item js-form-type-email form-item-email-confirm-inline-mail-2 js-form-item-email-confirm-inline-mail-2">'),
    );

    // Check flexbox submit.
    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_flexbox[mail_1]' => 'example01@example.com',
      'email_confirm_flexbox[mail_2]' => 'example02@example.com',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The specified email addresses do not match.');

    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_flexbox[mail_1]' => 'example@example.com',
      'email_confirm_flexbox[mail_2]' => 'example@example.com',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("email_confirm_basic: ''
email_confirm_advanced: ''
email_confirm_pattern: ''
email_confirm_required: example@example.com
email_confirm_flexbox: example@example.com");

    // Check email confirm invalid email addresses.
    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_advanced[mail_1]' => 'Not a valid email address',
      'email_confirm_advanced[mail_2]' => 'Not a valid email address, again',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The email address <em class="placeholder">Not a valid email address</em> is not valid.');
    $assert_session->responseContains('The email address <em class="placeholder">Not a valid email address, again</em> is not valid.');

    // Check email confirm non-matching email addresses.
    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example01@example.com',
      'email_confirm_advanced[mail_2]' => 'example02@example.com',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The specified email addresses do not match.');

    // Check email confirm matching email addresses.
    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_advanced[mail_1]' => 'example@example.com',
      'email_confirm_advanced[mail_2]' => 'example@example.com',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<li class="messages__item">The specified email addresses do not match.</li>');
    $assert_session->responseContains('email_confirm_advanced: example@example.com');

    // Check email confirm empty confirm email address.
    $this->drupalGet('/webform/test_element_email_confirm');
    $edit = [
      'email_confirm_advanced[mail_1]' => '',
      'email_confirm_advanced[mail_2]' => '',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseNotContains('<li class="messages__item">Confirm Email field is required.</li>');
  }

}
