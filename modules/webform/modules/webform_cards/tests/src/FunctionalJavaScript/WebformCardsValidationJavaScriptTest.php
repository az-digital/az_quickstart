<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards validation.
 *
 * @group webform_cards
 */
class WebformCardsValidationJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards validation.
   */
  public function testValidation() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_cards_validation_errors');

    // Submit form with server-side validation errors.
    $page->pressButton('edit-cards-next');
    $page->pressButton('edit-cards-next');
    $page->pressButton('edit-submit');

    // Check that only the card with validation error is visible.
    $assert_session->waitForElement('css', '.messages.messages--error');
    $this->assertElementNotVisible('[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_2"]');
    $this->assertElementNotVisible('[data-webform-key="card_3"]');
    $this->assertElementNotVisible('[data-webform-key="card_1"]');
    $this->assertElementNotVisible('#edit-cards-prev');
    $this->assertElementNotVisible('#edit-cards-next');
    $this->assertElementVisible('#edit-submit');
    $assert_session->responseContains('The email address <em class="placeholder">{email_multiple not valid}</em> is not valid.');
  }

}
