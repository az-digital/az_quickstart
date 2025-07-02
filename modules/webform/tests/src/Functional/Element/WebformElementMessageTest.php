<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for message webform element.
 *
 * @group webform
 */
class WebformElementMessageTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform_test_message_custom'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_message'];

  /**
   * Tests message element.
   */
  public function testMessage() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_message');

    $this->drupalGet('/webform/test_element_message');

    // Check basic message.
    $assert_session->responseContains('<div data-drupal-selector="edit-message-info" class="webform-message js-webform-message js-form-wrapper form-wrapper" id="edit-message-info">');
    $assert_session->responseContains('<div role="contentinfo" aria-label="Information message">');
    $assert_session->responseContains('This is an <strong>info</strong> message.');

    // Check close message with slide effect.
    $assert_session->responseContains('<div data-drupal-selector="edit-message-close-slide" class="webform-message js-webform-message webform-message--close js-webform-message--close js-form-wrapper form-wrapper" data-message-close-effect="slide" id="edit-message-close-slide">');
    $assert_session->responseContains('<div role="contentinfo" aria-label="Information message">');
    $assert_session->responseContains('<a href="#close" aria-label="close" class="js-webform-message__link webform-message__link">×</a>This is message that can be <b>closed using slide effect</b>.');

    // Set user and state storage.
    $elements = [
      'message_close_storage_user' => $webform->getElementDecoded('message_close_storage_user'),
      'message_close_storage_state' => $webform->getElementDecoded('message_close_storage_state'),
      'message_close_storage_custom' => $webform->getElementDecoded('message_close_storage_custom'),
    ];
    $webform->setElements($elements);
    $webform->save();

    // Check that close links are not enabled for 'user' or 'state' storage
    // for anonymous users.
    $this->drupalGet('/webform/test_element_message');
    $assert_session->responseContains('href="#close"');
    $assert_session->responseNotContains('data-message-storage="user"');
    $assert_session->responseNotContains('data-message-storage="state"');

    // Login to test closing message via 'user' and 'state' storage.
    $this->drupalLogin($this->drupalCreateUser());

    // Check that close links are enabled.
    $this->drupalGet('/webform/test_element_message');
    $assert_session->responseNotContains('href="#close"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-user"');
    $assert_session->responseContains('data-message-storage="user"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-state"');
    $assert_session->responseContains('data-message-storage="state"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-custom"');
    $assert_session->responseContains('data-message-storage="custom"');

    // Close message using 'user' storage.
    $this->drupalGet('/webform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'user' storage message is removed.
    $this->drupalGet('/webform/test_element_message');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-user"');
    $assert_session->responseNotContains('data-message-storage="user"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-state"');
    $assert_session->responseContains('data-message-storage="state"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-custom"');
    $assert_session->responseContains('data-message-storage="custom"');

    // Close message using 'state' storage.
    $this->drupalGet('/webform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'state' and 'user' storage message is removed.
    $this->drupalGet('/webform/test_element_message');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-user"');
    $assert_session->responseNotContains('data-message-storage="user"');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-state"');
    $assert_session->responseNotContains('data-message-storage="state"');
    $assert_session->responseContains('data-drupal-selector="edit-message-close-storage-custom"');
    $assert_session->responseContains('data-message-storage="custom"');

    // Close message using 'custom' storage.
    $this->drupalGet('/webform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'state' and 'user' storage message is removed.
    $this->drupalGet('/webform/test_element_message');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-user"');
    $assert_session->responseNotContains('data-message-storage="user"');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-state"');
    $assert_session->responseNotContains('data-message-storage="state"');
    $assert_session->responseNotContains('data-drupal-selector="edit-message-close-storage-custom"');
    $assert_session->responseNotContains('data-message-storage="custom"');

  }

}
