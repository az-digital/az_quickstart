<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards draft.
 *
 * @group webform_cards
 */
class WebformCardsDraftJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards draft.
   */
  public function testDraft() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    // Get the webform and load card 1.
    $this->drupalGet('/webform/test_cards_draft');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_1"]');
    $this->assertElementNotVisible('[data-webform-key="card_2"]');

    // Move to card 2.
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $this->assertElementNotVisible('[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_2"]');

    // Save a draft.
    $page->pressButton('edit-draft');
    $assert_session->responseContains('Submission saved. You may return to this form later and it will restore the current values.');
    $this->assertElementNotVisible('[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_2"]');

    // Reload the webform.
    $this->drupalGet('/webform/test_cards_draft');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $this->assertElementNotVisible('[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_2"]');
  }

}
