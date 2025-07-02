<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform settings behaviors.
 *
 * @group webform
 */
class WebformSettingsBehaviorsTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_submit_once',
    'test_form_disable_back',
    'test_form_submit_back',
    'test_form_unsaved',
    'test_form_disable_autocomplete',
    'test_form_novalidate',
    'test_form_disable_inline_errors',
    'test_form_required',
    'test_form_autofocus',
    'test_form_details_toggle',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Disable description help icon.
    $this->config('webform.settings')->set('ui.description_help', FALSE)->save();
  }

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */
    /* Test webform submit once (form_submit_once) */
    /* ********************************************************************** */

    $webform_form_submit_once = Webform::load('test_form_submit_once');

    // Check webform has webform.form.submit_once.js.
    $this->drupalGet('/webform/test_form_submit_once');
    $assert_session->responseContains('webform.form.submit_once.js');

    // Disable YAML specific form_submit_once setting.
    $webform_form_submit_once->setSetting('form_submit_once', FALSE);
    $webform_form_submit_once->save();

    // Check submit once checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_submit_once/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-submit-once" aria-describedby="edit-form-submit-once--description" type="checkbox" id="edit-form-submit-once" name="form_submit_once" value class="form-checkbox" />');

    // Check webform no longer has webform.form.submit_once.js.
    $this->drupalGet('/webform/test_form_submit_once');
    $assert_session->responseNotContains('webform.form.submit_once.js');

    // Enable default (global) submit_once on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_submit_once', TRUE)
      ->save();

    // Check submit_once checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_submit_once/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-submit-once-disabled" aria-describedby="edit-form-submit-once-disabled--description" disabled="disabled" type="checkbox" id="edit-form-submit-once-disabled" name="form_submit_once_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Submit button is disabled immediately after it is clicked for all forms.');

    // Check webform has webform.form.submit_once.js.
    $this->drupalGet('/webform/test_form_submit_once');
    $assert_session->responseContains('webform.form.submit_once.js');

    /* ********************************************************************** */
    /* Test webform disable back button (form_disable_back) */
    /* ********************************************************************** */

    $webform_form_disable_back = Webform::load('test_form_disable_back');

    // Check webform has webform.form.disable_back.js.
    $this->drupalGet('/webform/test_form_disable_back');
    $assert_session->responseContains('webform.form.disable_back.js');

    // Disable webform specific form_disable_back setting.
    $webform_form_disable_back->setSetting('form_disable_back', FALSE);
    $webform_form_disable_back->save();

    // Check disable_back checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_disable_back/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-disable-back" aria-describedby="edit-form-disable-back--description" type="checkbox" id="edit-form-disable-back" name="form_disable_back" value class="form-checkbox" />');

    // Check webform no longer has webform.form.disable_back.js.
    $this->drupalGet('/webform/test_form_disable_back');
    $assert_session->responseNotContains('webform.form.disable_back.js');

    // Enable default (global) disable_back on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_disable_back', TRUE)
      ->save();

    // Check disable_back checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_disable_back/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-disable-back-disabled" aria-describedby="edit-form-disable-back-disabled--description" disabled="disabled" type="checkbox" id="edit-form-disable-back-disabled" name="form_disable_back_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Back button is disabled for all forms.');

    // Check webform has webform.form.disable_back.js.
    $this->drupalGet('/webform/test_form_disable_back');
    $assert_session->responseContains('webform.form.disable_back.js');

    /* ********************************************************************** */
    /* Test webform submit back button (test_form_submit_back) */
    /* ********************************************************************** */

    $webform_form_submit_back = Webform::load('test_form_submit_back');

    // Check webform has webform.form.submit_back.js.
    $this->drupalGet('/webform/test_form_submit_back');
    $assert_session->responseContains('webform.form.submit_back.js');

    // Disable YAML specific form_submit_back setting.
    $webform_form_submit_back->setSetting('form_submit_back', FALSE);
    $webform_form_submit_back->save();

    // Check submit_back checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_submit_back/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-submit-back" aria-describedby="edit-form-submit-back--description" type="checkbox" id="edit-form-submit-back" name="form_submit_back" value class="form-checkbox" />');

    // Check webform no longer has webform.form.submit_back.js.
    $this->drupalGet('/webform/test_form_submit_back');
    $assert_session->responseNotContains('webform.form.submit_back.js');

    // Enable default (global) submit_back on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_submit_back', TRUE)
      ->save();

    // Check submit_back checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_submit_back/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-submit-back-disabled" aria-describedby="edit-form-submit-back-disabled--description" disabled="disabled" type="checkbox" id="edit-form-submit-back-disabled" name="form_submit_back_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Browser back button submits the previous page for all forms.');

    // Check webform has webform.form.submit_back.js.
    $this->drupalGet('/webform/test_form_submit_back');
    $assert_session->responseContains('webform.form.submit_back.js');

    // Enable Ajax support.
    $webform_form_submit_back->setSetting('ajax', TRUE);
    $webform_form_submit_back->save();

    // Check webform does have webform.form.submit_back.js when
    // Ajax is enabled.
    $this->drupalGet('/webform/test_form_submit_back');
    $assert_session->responseNotContains('webform.form.submit_back.js');

    /* ********************************************************************** */
    /* Test webform (client-side) unsaved (form_unsaved) */
    /* ********************************************************************** */

    $webform_form_unsaved = Webform::load('test_form_unsaved');

    // Check webform has .js-webform-unsaved class.
    $this->drupalGet('/webform/test_form_unsaved');
    $this->assertCssSelect('form.js-webform-unsaved', 'Form has .js-webform-unsaved class.');

    // Disable YAML specific webform unsaved setting.
    $webform_form_unsaved->setSetting('form_unsaved', FALSE);
    $webform_form_unsaved->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_unsaved/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-unsaved" aria-describedby="edit-form-unsaved--description" type="checkbox" id="edit-form-unsaved" name="form_unsaved" value class="form-checkbox" />');

    // Check webform no longer has .js-webform-unsaved class.
    $this->drupalGet('/webform/test_form_novalidate');
    $this->assertNoCssSelect('webform.js-webform-unsaved', 'Webform does not have .js-webform-unsaved class.');

    // Enable default (global) unsaved on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_unsaved', TRUE)
      ->save();

    // Check unsaved checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_unsaved/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-unsaved-disabled" aria-describedby="edit-form-unsaved-disabled--description" disabled="disabled" type="checkbox" id="edit-form-unsaved-disabled" name="form_unsaved_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Unsaved warning is enabled for all forms.');

    // Check unsaved attribute added to webform.
    $this->drupalGet('/webform/test_form_unsaved');
    $this->assertCssSelect('form.js-webform-unsaved', 'Form has .js-webform-unsaved class.');

    /* ********************************************************************** */
    /* Test webform disable autocomplete (form_disable_autocomplete) */
    /* ********************************************************************** */

    // Check webform has autocomplete=off attribute.
    $this->drupalGet('/webform/test_form_disable_autocomplete');
    $this->assertCssSelect('form[autocomplete="off"]', 'Form has autocomplete=off attribute.');

    /* ********************************************************************** */
    /* Test webform (client-side) novalidate (form_novalidate) */
    /* ********************************************************************** */

    $webform_form_novalidate = Webform::load('test_form_novalidate');

    // Check webform has novalidate attribute.
    $this->drupalGet('/webform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', 'Form has the proper novalidate attribute.');

    // Disable YAML specific webform client-side validation setting.
    $webform_form_novalidate->setSetting('form_novalidate', FALSE);
    $webform_form_novalidate->save();

    // Check novalidate checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_novalidate/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-novalidate" aria-describedby="edit-form-novalidate--description" type="checkbox" id="edit-form-novalidate" name="form_novalidate" value class="form-checkbox" />');
    $assert_session->responseContains('If checked, the <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/form">novalidate</a> attribute, which disables client-side validation, will be added to this form.');

    // Check webform no longer has novalidate attribute.
    $this->drupalGet('/webform/test_form_novalidate');
    $this->assertNoCssSelect('form[novalidate="novalidate"]', 'Webform have client-side validation enabled.');

    // Enable default (global) disable client-side validation on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_novalidate', TRUE)
      ->save();

    // Check novalidate checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_novalidate/settings/form');
    $assert_session->responseNotContains('If checked, the <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/form">novalidate</a> attribute, which disables client-side validation, will be added to this form.');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-novalidate-disabled" aria-describedby="edit-form-novalidate-disabled--description" disabled="disabled" type="checkbox" id="edit-form-novalidate-disabled" name="form_novalidate_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Client-side validation is disabled for all forms.');

    // Check novalidate attribute added to webform.
    $this->drupalGet('/webform/test_form_novalidate');
    $this->assertCssSelect('form[novalidate="novalidate"]', 'Form has the proper novalidate attribute.');

    /* ********************************************************************** */
    /* Test required indicator (form_required) */
    /* ********************************************************************** */

    $webform_form_required = Webform::load('test_form_required');

    // Check webform has required indicator.
    $this->drupalGet('/webform/test_form_required');
    $assert_session->responseContains('Indicates required field');

    // Disable required indicator.
    $webform_form_required->setSetting('form_required', FALSE);
    $webform_form_required->save();

    // Check webform does not have have required indicator.
    $this->drupalGet('/webform/test_form_required');
    $assert_session->responseNotContains('Indicates required field');

    // Enable default (global) required indicator on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_required', TRUE)
      ->set('settings.default_form_required_label', 'Custom required field')
      ->save();

    // Check required checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_required/settings/form');
    $assert_session->responseContains('Required indicator is displayed on all forms.');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-required-disabled" aria-describedby="edit-form-required-disabled--description" disabled="disabled" type="checkbox" id="edit-form-required-disabled" name="form_required_disabled" value="1" checked="checked" class="form-checkbox" />');

    // Check global required indicator added to webform.
    $this->drupalGet('/webform/test_form_required');
    $assert_session->responseContains('Custom required field');

    $elements = $webform_form_required->getElementsDecoded();
    unset($elements['textfield']['#required']);
    $webform_form_required->setElements($elements);
    $webform_form_required->save();

    // Check required indicator not added to webform with no required elements.
    $this->drupalGet('/webform/test_form_required');
    $assert_session->responseNotContains('Custom required field');

    /* ********************************************************************** */
    /* Test autofocus (form_autofocus) */
    /* ********************************************************************** */

    // Check webform has autofocus class.
    $this->drupalGet('/webform/test_form_autofocus');
    $this->assertCssSelect('.js-webform-autofocus');

    /* ********************************************************************** */
    /* Test webform details toggle (form_details_toggle) */
    /* ********************************************************************** */

    $webform_form_details_toggle = Webform::load('test_form_details_toggle');

    // Check webform has .webform-details-toggle class.
    $this->drupalGet('/webform/test_form_details_toggle');
    $this->assertCssSelect('form.webform-details-toggle', 'Form has the .webform-details-toggle class.');

    // Check details toggle checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_details_toggle/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-details-toggle-disabled" aria-describedby="edit-form-details-toggle-disabled--description" disabled="disabled" type="checkbox" id="edit-form-details-toggle-disabled" name="form_details_toggle_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Expand/collapse all (details) link is automatically added to all forms.');

    // Disable default (global) details toggle on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_details_toggle', FALSE)
      ->save();

    // Check .webform-details-toggle class still added to webform.
    $this->drupalGet('/webform/test_form_details_toggle');
    $this->assertCssSelect('form.webform-details-toggle', 'Form has the .webform-details-toggle class.');

    // Check details toggle checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_details_toggle/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-details-toggle" aria-describedby="edit-form-details-toggle--description" type="checkbox" id="edit-form-details-toggle" name="form_details_toggle" value checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('If checked, an expand/collapse all (details) link will be added to this webform when there are two or more details elements available on the webform.');

    // Disable YAML specific webform details toggle setting.
    $webform_form_details_toggle->setSetting('form_details_toggle', FALSE);
    $webform_form_details_toggle->save();

    // Check webform does not hav .webform-details-toggle class.
    $this->drupalGet('/webform/test_form_details_toggle');
    $this->assertNoCssSelect('webform.webform-details-toggle', 'Webform does not have the .webform-details-toggle class.');

    /* ********************************************************************** */
    /* Test webform disable inline form errors (test_form_disable_inline_errors) */
    /* ********************************************************************** */

    $webform_form_inline_errors = Webform::load('test_form_disable_inline_errors');

    // Check that error message is displayed at the top of the page.
    $this->postSubmission($webform_form_inline_errors);
    $assert_session->responseMatches('#<h2 class="visually-hidden">Error message</h2>\s+textfield field is required.#m');

    // Enable the inline form errors module.
    \Drupal::service('module_installer')->install(['inline_form_errors']);

    // Check that error message is still displayed at the top of the page.
    $this->postSubmission($webform_form_inline_errors);
    $assert_session->responseMatches('#<h2 class="visually-hidden">Error message</h2>\s+textfield field is required.#m');

    // Allow inline error message for this form.
    $webform_form_inline_errors->setSetting('form_disable_inline_errors', FALSE);
    $webform_form_inline_errors->save();

    // Check that error message is not displayed at the top of the page.
    $this->postSubmission($webform_form_inline_errors);
    $assert_session->responseNotMatches('#<h2 class="visually-hidden">Error message</h2>\s+textfield field is required.#m');

    // Check that error message is displayed inline.
    $assert_session->responseContains('1 error has been found: <ul class="item-list__comma-list"><li><a href="#edit-textfield">textfield</a></li></ul>');
    $assert_session->responseContains('textfield field is required.');

    // Check disable inline errors checkbox is enabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_disable_inline_errors/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-disable-inline-errors" aria-describedby="edit-form-disable-inline-errors--description" type="checkbox" id="edit-form-disable-inline-errors" name="form_disable_inline_errors" value class="form-checkbox" />');
    $assert_session->responseContains('If checked, <a href="https://www.drupal.org/docs/8/core/modules/inline-form-errors/inline-form-errors-module-overview">inline form errors</a> will be disabled for this form.');

    // Enable default (global) disable inline form errors on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_form_disable_inline_errors', TRUE)
      ->save();

    // Check novalidate checkbox is disabled.
    $this->drupalGet('/admin/structure/webform/manage/test_form_disable_inline_errors/settings/form');
    $assert_session->responseContains('<input data-drupal-selector="edit-form-disable-inline-errors-disabled" aria-describedby="edit-form-disable-inline-errors-disabled--description" disabled="disabled" type="checkbox" id="edit-form-disable-inline-errors-disabled" name="form_disable_inline_errors_disabled" value="1" checked="checked" class="form-checkbox" />');
    $assert_session->responseContains('Inline form errors is disabled for all forms.');

    // Check that error message is not displayed inline.
    $assert_session->responseNotContains('1 error has been found: <ul class="item-list__comma-list"><li><a href="#edit-textfield">textfield</a></li></ul>');
    $assert_session->responseNotContains('textfield field is required.');
  }

}
