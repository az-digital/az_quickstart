<?php
// phpcs:ignoreFile

namespace Drupal\Tests\webform\FunctionalJavascript\States;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform (custom) #states required logic.
 *
 * @group webform_javascript
 */
class WebformStatesRequiredJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_clientside_validation', 'file'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_client_required',
  ];

  /**
   * Tests webform (custom) #states required logic.
   */
  public function testRequiredState() {
    // @todo Fix broken test on Drupal.org that working as expected locally.
    $this->assertTrue(TRUE);
    return;

    $session = $this->getSession();
    $page = $session->getPage();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_states_client_required');

    // Check that all of the static radios are required.
    $this->assertCssSelect('#edit-radios-static-one[required]');
    $this->assertCssSelect('#edit-radios-static-two[required]');
    $this->assertCssSelect('#edit-radios-static-three[required]');

    // Check that all of the static radios has required message.
    $this->assertCssSelect('#edit-radios-static-one[data-msg-required="radios_static field is required."]');
    $this->assertCssSelect('#edit-radios-static-two[data-msg-required="radios_static field is required."]');
    $this->assertCssSelect('#edit-radios-static-three[data-msg-required="radios_static field is required."]');

    // Check that all of the static radios other are required.
    $this->assertCssSelect('#edit-radios-other-static-radios-one[required]');
    $this->assertCssSelect('#edit-radios-other-static-radios-two[required]');
    $this->assertCssSelect('#edit-radios-other-static-radios-three[required]');

    // Check that only the first static checkbox is required.
    $this->assertCssSelect('#edit-checkboxes-static-one[required]');
    $this->assertNoCssSelect('#edit-checkboxes-static-two[required]');
    $this->assertNoCssSelect('#edit-checkboxes-static-three[required]');

    // Check that only the first static checkboxes has require message.
    $this->assertCssSelect('#edit-checkboxes-static-one[data-msg-required="checkboxes_static field is required."]');
    $this->assertNoCssSelect('#edit-checkboxes-static-two[data-msg-required="checkboxes_static field is required."]');
    $this->assertNoCssSelect('#edit-checkboxes-static-three[data-msg-required="checkboxes_static field is required."]');

    // Check that only the first static checkboxes is required.
    $this->assertCssSelect('#edit-checkboxes-other-static-checkboxes-one[required]');
    $this->assertNoCssSelect('#edit-checkboxes-other-static-checkboxes--two[required]');
    $this->assertNoCssSelect('#edit-checkboxes-other-static-checkboxes--three[required]');

    // Check that file upload is not required.
    $this->assertNoCssSelect('#edit-managed-file-upload[required]');

    // Check that radios and checkboxes fieldset wrapper is not required.
    $this->assertNoCssSelect('[data-drupal-selector="edit-radios"] .fieldset-legend.js-form-required.form-required');
    $this->assertNoCssSelect('[data-drupal-selector="edit-checkboxes"] .fieldset-legend.js-form-required.form-required');

    // Check that none of the radios are required.
    $this->assertNoCssSelect('#edit-radios-one[required]');
    $this->assertNoCssSelect('#edit-radios-two[required]');
    $this->assertNoCssSelect('#edit-radios-three[required]');

    // Check that none of the checkboxes are required.
    $this->assertNoCssSelect('#edit-checkboxes-one[required]');
    $this->assertNoCssSelect('#edit-checkboxes-two[required]');
    $this->assertNoCssSelect('#edit-checkboxes-three[required]');

    // Check select other is not required.
    $this->assertNoCssSelect('#edit-select-other-select[required]');

    // Trigger the required state for all elements.
    $page->checkField('trigger');

    // Check that file upload is required.
    $this->assertCssSelect('#edit-managed-file-upload[required]');

    // Check that radios and checkbox fieldset wrapper is required.
    $this->assertCssSelect('[data-drupal-selector="edit-radios"] .fieldset-legend.js-form-required.form-required');
    $this->assertCssSelect('[data-drupal-selector="edit-checkboxes"] .fieldset-legend.js-form-required.form-required');

    // Check that all of the radios are required.
    $this->assertCssSelect('#edit-radios-one[required]');
    $this->assertCssSelect('#edit-radios-two[required]');
    $this->assertCssSelect('#edit-radios-three[required]');

    // Check that all of the radios has required message.
    $this->assertCssSelect('#edit-radios-one[data-msg-required="radios field is required."]');
    $this->assertCssSelect('#edit-radios-two[data-msg-required="radios field is required."]');
    $this->assertCssSelect('#edit-radios-three[data-msg-required="radios field is required."]');

    // Check that all of the radios other are required.
    $this->assertCssSelect('#edit-radios-other-radios-one[required]');
    $this->assertCssSelect('#edit-radios-other-radios-two[required]');
    $this->assertCssSelect('#edit-radios-other-radios-three[required]');

    // Check that only the first checkbox is required.
    $this->assertCssSelect('#edit-checkboxes-one[required]');
    $this->assertNoCssSelect('#edit-checkboxes-two[required]');
    $this->assertNoCssSelect('#edit-checkboxes-three[required]');

    // Check that only the first checkbox has require message.
    $this->assertCssSelect('#edit-checkboxes-one[data-msg-required="checkboxes field is required."]');
    $this->assertNoCssSelect('#edit-checkboxes-two[data-msg-required="checkboxes field is required."]');
    $this->assertNoCssSelect('#edit-checkboxes-three[data-msg-required="checkboxes field is required."]');

    // Check that only the first checkbox other is required.
    $this->assertCssSelect('#edit-checkboxes-other-checkboxes-one[required]');
    $this->assertNoCssSelect('#edit-checkboxes-other-checkboxes-two[required]');
    $this->assertNoCssSelect('#edit-checkboxes-other-checkboxes-three[required]');

    // Check select other is required.
    $this->assertCssSelect('#edit-select-other-select[required]');

    // Check select other label without [for] attribute is required.
    $this->assertNoCssSelect('#edit-select-other-form-element > label[for]');
    $this->assertCssSelect('#edit-select-other-form-element > label.js-form-required.form-required');

    // Check that fieldsets are never required.
    $this->assertNoCssSelect('fieldset#edit-fieldset[required]');

    // Check checking the first checkbox removes the [required] attribute.
    $page->checkField('edit-checkboxes-one');
    $this->assertNoCssSelect('#edit-checkboxes-one[required]');

    // Check unchecking the first checkbox restores the [required] attribute.
    $page->uncheckField('edit-checkboxes-one');
    $this->assertCssSelect('#edit-checkboxes-one[required]');

    // Check checking the first checkbox removes the [required] attribute.
    $page->checkField('edit-checkboxes-two');
    $this->assertNoCssSelect('#edit-checkboxes-one[required]');
  }

}
