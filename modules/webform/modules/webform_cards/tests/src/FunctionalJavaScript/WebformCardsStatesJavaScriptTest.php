<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards states.
 *
 * @group webform_cards
 */
class WebformCardsStatesJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards states.
   */
  public function testStates() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    // Check that all progress steps are visible.
    $this->drupalGet('/webform/test_cards_states');
    $this->assertElementVisible('[data-webform-card="start"][title="Start"]');
    $this->assertElementVisible('[data-webform-card="card_1"][title="Card 1"]');
    $this->assertElementVisible('[data-webform-card="card_2"][title="Card 2"]');
    $this->assertElementVisible('[data-webform-card="card_3"][title="Card 3"]');
    $this->assertElementVisible('[data-webform-card="card_4"][title="Card 4"]');
    $this->assertElementVisible('[data-webform-card="card_5"][title="Card 5"]');
    $this->assertElementVisible('[data-webform-page="webform_preview"][title="Preview"]');
    $this->assertElementVisible('[data-webform-page="webform_confirmation"][title="Complete"]');
    $this->assertCssSelect('.webform-card--active[data-webform-key="start"]');

    // Check that card 1 is next.
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertCssSelect('.webform-card--active[data-webform-key="card_1"]');

    // Go back to states.
    $page->pressButton('edit-cards-prev');

    // Check that progress steps can be conditionally hidden.
    $this->click('#edit-trigger-cards-card-1');
    $this->click('#edit-trigger-cards-card-3');
    $this->click('#edit-trigger-cards-card-5');
    $this->assertElementNotVisible('[data-webform-card="card_1"][title="Card 1"]');
    $this->assertElementNotVisible('[data-webform-card="card_3"][title="Card 3"]');
    $this->assertElementNotVisible('[data-webform-card="card_5"][title="Card 5"]');

    // Check that card 2 is now next.
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $this->assertCssSelect('.webform-card--active[data-webform-key="card_2"]');

    // Go back to states.
    $page->pressButton('edit-cards-prev');

    // Check that progress steps can be conditionally shown.
    $this->click('#edit-trigger-cards-card-1');
    $this->click('#edit-trigger-cards-card-3');
    $this->click('#edit-trigger-cards-card-5');
    $this->assertElementVisible('[data-webform-card="card_1"][title="Card 1"]');
    $this->assertElementVisible('[data-webform-card="card_3"][title="Card 3"]');
    $this->assertElementVisible('[data-webform-card="card_5"][title="Card 5"]');

    // Check that card 1 is now next.
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertCssSelect('.webform-card--active[data-webform-key="card_1"]');
  }

}
