<?php

namespace Drupal\Tests\devel\Functional;

/**
 * Tests devel error handler.
 *
 * @group devel
 */
class DevelErrorHandlerTest extends DevelBrowserTestBase {

  /**
   * Tests devel error handler.
   */
  public function testErrorHandler(): void {
    $messages_selector = '[data-drupal-messages]';

    $expected_notice = 'This is an example notice';
    $expected_warning = 'This is an example warning';

    $config = $this->config('system.logging');
    $config->set('error_level', ERROR_REPORTING_DISPLAY_VERBOSE)->save();

    $this->drupalLogin($this->adminUser);

    // Ensures that the error handler config is present on the config page and
    // by default the standard error handler is selected.
    $error_handlers = \Drupal::config('devel.settings')->get('error_handlers');
    $this->assertEquals($error_handlers, [DEVEL_ERROR_HANDLER_STANDARD => DEVEL_ERROR_HANDLER_STANDARD]);
    $this->drupalGet('admin/config/development/devel');
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_STANDARD)->hasAttribute('selected'));

    // Ensures that selecting the DEVEL_ERROR_HANDLER_NONE option no error
    // (raw or message) is shown on the site in case of php errors.
    $edit = [
      'error_handlers[]' => DEVEL_ERROR_HANDLER_NONE,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $error_handlers = \Drupal::config('devel.settings')->get('error_handlers');
    $this->assertEquals($error_handlers, [DEVEL_ERROR_HANDLER_NONE => DEVEL_ERROR_HANDLER_NONE]);
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_NONE)->hasAttribute('selected'));

    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    // @todo Two assertions commented out. Can be fixed in conjunction with the following two issues.
    // @see https://gitlab.com/drupalspoons/devel/-/issues/420
    // @see https://gitlab.com/drupalspoons/devel/-/issues/454
    // $this->assertSession()->pageTextNotContains($expected_notice);
    // $this->assertSession()->pageTextNotContains($expected_warning);
    $this->assertSession()->elementNotExists('css', $messages_selector);

    // Ensures that selecting the DEVEL_ERROR_HANDLER_BACKTRACE_KINT option a
    // backtrace above the rendered page is shown on the site in case of php
    // errors.
    $edit = [
      'error_handlers[]' => DEVEL_ERROR_HANDLER_BACKTRACE_KINT,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $error_handlers = \Drupal::config('devel.settings')->get('error_handlers');
    $this->assertEquals($error_handlers, [DEVEL_ERROR_HANDLER_BACKTRACE_KINT => DEVEL_ERROR_HANDLER_BACKTRACE_KINT]);
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_BACKTRACE_KINT)->hasAttribute('selected'));

    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementNotExists('css', $messages_selector);

    // Ensures that selecting the DEVEL_ERROR_HANDLER_BACKTRACE_DPM option a
    // backtrace in the message area is shown on the site in case of php errors.
    $edit = [
      'error_handlers[]' => DEVEL_ERROR_HANDLER_BACKTRACE_DPM,
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $error_handlers = \Drupal::config('devel.settings')->get('error_handlers');
    $this->assertEquals($error_handlers, [DEVEL_ERROR_HANDLER_BACKTRACE_DPM => DEVEL_ERROR_HANDLER_BACKTRACE_DPM]);
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_BACKTRACE_DPM)->hasAttribute('selected'));

    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_notice);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_warning);

    // Ensures that when multiple handlers are selected, the output produced by
    // every handler is shown on the site in case of php errors.
    $edit = [
      'error_handlers[]' => [
        DEVEL_ERROR_HANDLER_BACKTRACE_KINT => DEVEL_ERROR_HANDLER_BACKTRACE_KINT,
        DEVEL_ERROR_HANDLER_BACKTRACE_DPM => DEVEL_ERROR_HANDLER_BACKTRACE_DPM,
      ],
    ];
    $this->submitForm($edit, 'Save configuration');
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    $error_handlers = \Drupal::config('devel.settings')->get('error_handlers');
    $this->assertEquals($error_handlers, [
      DEVEL_ERROR_HANDLER_BACKTRACE_KINT => DEVEL_ERROR_HANDLER_BACKTRACE_KINT,
      DEVEL_ERROR_HANDLER_BACKTRACE_DPM => DEVEL_ERROR_HANDLER_BACKTRACE_DPM,
    ]);
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_BACKTRACE_KINT)->hasAttribute('selected'));
    $this->assertTrue($this->assertSession()->optionExists('edit-error-handlers', (string) DEVEL_ERROR_HANDLER_BACKTRACE_DPM)->hasAttribute('selected'));

    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_notice);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_warning);

    // Ensures that setting the error reporting to all the output produced by
    // handlers is shown on the site in case of php errors.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_ALL)->save();
    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_notice);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_warning);

    // Ensures that setting the error reporting to some the output produced by
    // handlers is shown on the site in case of php errors.
    $config->set('error_level', ERROR_REPORTING_DISPLAY_SOME)->save();
    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_notice);
    $this->assertSession()->elementContains('css', $messages_selector, $expected_warning);

    // Ensures that setting the error reporting to none the output produced by
    // handlers is not shown on the site in case of php errors.
    $config->set('error_level', ERROR_REPORTING_HIDE)->save();
    $this->clickLink('notice+warning');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains($expected_notice);
    $this->assertSession()->pageTextNotContains($expected_warning);
    $this->assertSession()->elementNotExists('css', $messages_selector);
  }

}
