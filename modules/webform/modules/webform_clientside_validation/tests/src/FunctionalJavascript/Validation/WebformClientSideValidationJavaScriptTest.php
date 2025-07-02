<?php

namespace Drupal\Tests\webform_clientside_validation\FunctionalJavascript\Validation;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform submission with client side validation.
 *
 * @group webform_javascript
 */
class WebformClientSideValidationJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'file', 'webform_clientside_validation_test', 'webform_clientside_validation'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_clientside_validation',
    'test_clientside_validation_state',
  ];

  /**
   * Tests custom states.
   */
  public function testClientSideValidation() {
    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Table select.
    /* ********************************************************************** */

    // Check that all radios and checkbox tables triggers client side validation.
    $this->drupalGet('/webform/test_clientside_validation');
    $this->assertCssSelect('#edit-tableselect-checkboxes.required');
    $this->assertCssSelect('#edit-tableselect-checkboxes-one[required]');
    $this->assertCssSelect('#edit-tableselect-radios.required');
    $this->assertCssSelect('#edit-tableselect-radios-one[required]');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('This field is required.');
    $assert_session->waitForText('tableselect_radios field is required.');

    // Check that all radios and checkbox tables triggers client side validation
    // with conditional logic.
    $this->drupalGet('/webform/test_clientside_validation_state');
    $this->assertNoCssSelect('#edit-tableselect-checkboxes.required');
    $this->assertNoCssSelect('#edit-tableselect-checkboxes-one[required]');
    $this->assertNoCssSelect('#edit-tableselect-radios.required');
    $this->assertNoCssSelect('#edit-tableselect-radios-one[required]');

    $this->click('#edit-trigger');
    $this->assertCssSelect('#edit-tableselect-checkboxes.required');
    $this->assertCssSelect('#edit-tableselect-checkboxes-one[required]');
    $this->assertCssSelect('#edit-tableselect-radios.required');
    $this->assertCssSelect('#edit-tableselect-radios-one[required]');

    /* ********************************************************************** */
    // Other elements.
    /* ********************************************************************** */

    // Check that custom 'other' error messages work.
    $this->drupalGet('/webform/test_clientside_validation');
    $page->findById('edit-select-other-select')->selectOption('_other_');
    $page->findById('edit-radios-other-radios-other-')->selectOption('_other_');
    $page->findById('edit-checkboxes-other-checkboxes-other-')->check();
    $this->submitForm([], 'Submit');
    $custom_errors = [
      'select-other' => 'Custom select_other required message.',
      'checkboxes-other' => 'Custom checkboxes_other required message.',
      'radios-other' => 'Custom radios_other required message.',
    ];
    foreach ($custom_errors as $element_type => $expected_error) {
      $element = $page->find('css', "#edit-$element_type-other-error.error");
      static::assertNotNull($element);
      static::assertEquals($expected_error, $element->getText());
    }

  }

}
