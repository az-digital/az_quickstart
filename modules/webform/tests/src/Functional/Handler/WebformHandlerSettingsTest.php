<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for settings webform handler functionality.
 *
 * @group webform
 */
class WebformHandlerSettingsTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_settings'];

  /**
   * Test settings handler.
   */
  public function testSettingsHandler() {
    $assert_session = $this->assertSession();

    // NOTE: Using message indentation to make sure the message is matched
    // and not the input value.
    $message_indentation = '              ';

    // Check custom save draft message.
    $this->drupalGet('/webform/test_handler_settings');
    $edit = [
      'preview' => TRUE,
      'confirmation' => TRUE,
      'custom' => TRUE,
    ];
    $this->submitForm($edit, 'Save Draft');
    $assert_session->responseContains($message_indentation . '{Custom draft saved message}');

    // Check custom save load message.
    $this->drupalGet('/webform/test_handler_settings');
    // NOTE: Adding indentation to make sure the message is matched and not input value.
    $assert_session->responseContains($message_indentation . '{Custom draft loaded message}');

    // Check custom preview title and message.
    $this->drupalGet('/webform/test_handler_settings');
    $this->submitForm([], 'Preview');
    $assert_session->responseContains('<li>{Custom preview message}</li>');
    $assert_session->responseContains('<h1>{Custom preview title}</h1>');

    // Check custom confirmation title and message.
    $this->drupalGet('/webform/test_handler_settings');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('<h1>{Custom confirmation title}</h1>');
    $assert_session->responseContains('<div class="webform-confirmation__message">{Custom confirmation message}</div>');

    // Check no custom save draft message.
    $this->drupalGet('/webform/test_handler_settings');
    $edit = [
      'preview' => FALSE,
      'confirmation' => FALSE,
      'custom' => FALSE,
    ];
    $this->submitForm($edit, 'Save Draft');
    $assert_session->responseNotContains($message_indentation . '{Custom draft saved message}');

    // Check no custom save load message.
    $this->drupalGet('/webform/test_handler_settings');
    $assert_session->responseNotContains($message_indentation . '{Custom draft loaded message}');

    // Check no custom preview title and message.
    $this->drupalGet('/webform/test_handler_settings');
    $this->submitForm([], 'Preview');
    $assert_session->responseNotContains('<h1>{Custom confirmation title}</h1>');
    $assert_session->responseNotContains('<div class="webform-confirmation__message">{Custom confirmation message}</div>');

    // Check no custom confirmation title and message.
    $this->drupalGet('/webform/test_handler_settings');
    $this->submitForm([], 'Submit');
    $assert_session->responseNotContains('{Custom confirmation title}');
    $assert_session->responseNotContains('{Custom confirmation message}');
  }

}
