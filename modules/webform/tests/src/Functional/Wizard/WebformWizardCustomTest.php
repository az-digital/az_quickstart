<?php

namespace Drupal\Tests\webform\Functional\Wizard;

/**
 * Tests for webform custom wizard.
 *
 * @group webform
 */
class WebformWizardCustomTest extends WebformWizardTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_wizard_custom'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_custom'];

  /**
   * Test webform custom wizard.
   */
  public function testCustomWizard() {
    // Check current page is #1.
    $this->drupalGet('/webform/test_form_wizard_custom');
    $this->assertCurrentPage('Wizard page #1', 'wizard_1');

    // Check next page is #2.
    $this->drupalGet('/webform/test_form_wizard_custom');
    $this->submitForm([], 'Next >');
    $this->assertCurrentPage('Wizard page #2', 'wizard_2');

    // Check previous page is #1.
    $this->submitForm([], '< Previous');
    $this->assertCurrentPage('Wizard page #1', 'wizard_1');

    // Hide pages #3 and #4.
    $edit = [
      'pages[wizard_1]' => TRUE,
      'pages[wizard_2]' => TRUE,
      'pages[wizard_3]' => FALSE,
      'pages[wizard_4]' => FALSE,
      'pages[wizard_5]' => TRUE,
      'pages[wizard_6]' => TRUE,
      'pages[wizard_7]' => TRUE,
    ];
    $this->submitForm($edit, 'Next >');

    // Check next page is #2.
    $this->assertCurrentPage('Wizard page #2', 'wizard_2');

    // Check next page is #5.
    $this->submitForm([], 'Next >');
    $this->assertCurrentPage('Wizard page #5', 'wizard_5');

    // Check previous page is #2.
    $this->submitForm([], '< Previous');
    $this->assertCurrentPage('Wizard page #2', 'wizard_2');

    // Check previous page is #1.
    $this->submitForm([], '< Previous');
    $this->assertCurrentPage('Wizard page #1', 'wizard_1');
  }

}
