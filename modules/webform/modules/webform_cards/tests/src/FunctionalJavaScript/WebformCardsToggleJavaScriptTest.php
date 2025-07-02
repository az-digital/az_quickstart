<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards toggle show/hide all.
 *
 * @group webform_cards
 */
class WebformCardsToggleJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards toggle show/hide all.
   */
  public function testToggle() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_cards_toggle');
    $assert_session->waitForElement('css', 'button.webform-cards-toggle');

    // Check that only card 1 is visible.
    $this->assertElementVisible('[data-webform-key="card_1"]');
    $this->assertElementNotVisible('[data-webform-key="card_2"]');
    // Check that only next button is visible.
    $this->assertElementVisible('#edit-cards-next');
    $this->assertElementNotVisible('#edit-submit');
    // Check that progress is visible.
    $this->assertElementVisible('.webform-progress');

    // Press 'Show all' elements button.
    $page->pressButton('Show all');

    // Check that card 1 and 2 are visible.
    $this->assertElementVisible('[data-webform-key="card_1"]');
    $this->assertElementVisible('[data-webform-key="card_2"]');
    // Check that only submit button is visible.
    $this->assertElementNotVisible('#edit-cards-next');
    $this->assertElementVisible('#edit-submit');
    // Check that progress is not visible.
    $this->assertElementNotVisible('.webform-progress');

    // Press 'Hide all' elements button.
    $page->pressButton('Hide all');

    // Check that only card 1 is visible.
    $this->assertElementVisible('[data-webform-key="card_1"]');
    $this->assertElementNotVisible('[data-webform-key="card_2"]');
    // Check that only next button is visible.
    $this->assertElementVisible('#edit-cards-next');
    $this->assertElementNotVisible('#edit-submit');
    // Check that progress is visible.
    $this->assertElementVisible('.webform-progress');
  }

}
