<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform cards progress.
 *
 * @group webform_cards
 */
class WebformCardsProgressJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards progress.
   */
  public function testProgress() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_cards_progress');

    /* ********************************************************************** */
    // Progress (test_cards_progress).
    /* ********************************************************************** */

    // Get the webform and load card 1.
    $this->drupalGet('/webform/test_cards_progress');

    // Check that all the button are included on the page.
    $this->assertCssSelect('#edit-cards-prev');
    $this->assertCssSelect('#edit-cards-next');
    $this->assertCssSelect('#edit-preview-next');
    $this->assertCssSelect('#edit-submit');

    // Check that only next button is visible on card 1.
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertElementNotVisible('#edit-cards-prev');
    $this->assertElementVisible('#edit-cards-next');
    $this->assertElementNotVisible('#edit-preview-next');
    $this->assertElementNotVisible('#edit-submit');

    // Move to card 2.
    $page->pressButton('edit-cards-next');

    // Check that only previous, preview, and submit buttons are visible
    // on card 2.
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $assert_session->waitForElementVisible('css', '#edit-cards-prev');
    $this->assertElementVisible('#edit-cards-prev');
    $this->assertElementNotVisible('#edit-cards-next');
    $this->assertElementVisible('#edit-preview-next');
    $this->assertElementVisible('#edit-submit');

    // Move to preview.
    $page->pressButton('edit-preview-next');

    // Check that preview is loaded.
    $this->assertCssSelect('.webform-preview');

    // Check that only previous and submit buttons are visible on preview page.
    $this->assertElementVisible('#edit-preview-prev');
    $this->assertElementVisible('#edit-submit');

    /* ********************************************************************** */
    // Progress track.
    /* ********************************************************************** */

    // Enable tracking by name.
    $webform->setSetting('wizard_track', 'name')->save();

    // Check page 1 URL with ?page=*.
    $this->drupalGet('/webform/test_cards_progress');
    $this->assertQuery('page=card_1');

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-cards-next');
    $this->assertQuery('page=card_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-cards-prev');
    $this->assertQuery('page=card_1');

    // Check page 1 URL with custom param.
    $this->drupalGet('/webform/test_cards_progress', ['query' => ['custom_param' => '1']]);
    $this->assertQuery('custom_param=1&page=card_1');

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-cards-next');
    $this->assertQuery('custom_param=1&page=card_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-cards-prev');
    $this->assertQuery('custom_param=1&page=card_1');

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-cards-next');
    $this->assertQuery('custom_param=1&page=card_2');

    // Check preview URL with ?page=webform_preview.
    $page->pressButton('edit-preview-next');
    $this->assertQuery('custom_param=1&page=webform_preview');

    /* ********************************************************************** */
    // Progress confirmation.
    /* ********************************************************************** */

    // Check that confirmation is NOT included in progress.
    $this->drupalGet('/webform/test_cards_progress');
    $assert_session->pageTextNotContains('Complete');

    // AD confirmation TO progress.
    $webform->setSetting('wizard_confirmation', TRUE)->save();

    // Check that confirmation 'Complete' is included in progress.
    $this->drupalGet('/webform/test_cards_progress');
    $assert_session->pageTextContains('Complete');

    // Change the confirmation label to 'Done.
    $webform->setSetting('wizard_confirmation_label', 'Done')->save();

    // Check that confirmation 'Done' is included in progress.
    $this->drupalGet('/webform/test_cards_progress');
    $assert_session->pageTextNotContains('Complete');
    $assert_session->pageTextContains('Done');

    /* ********************************************************************** */
    // Progress bar and links (test_cards_progress_links).
    /* ********************************************************************** */

    // Get the webform and load card 1.
    $this->drupalGet('/webform/test_cards_progress_links');
    $assert_session->waitForElement('css', '#edit-element-1');

    // Check that no cards are linked in the progress bar.
    $this->assertNoCssSelect('[data-webform-card="card_1"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_1"] .progress-title[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-title[role="link"]');
    $this->assertCssSelect('.progress-step.is-active[data-webform-card="card_1"]');

    // Move to card 2.
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '#edit-element-2');

    // Check that only card 1 is linked in the progress bar.
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $assert_session->waitForElementVisible('css', '#edit-cards-prev');
    $this->assertCssSelect('[data-webform-card="card_1"] .progress-marker[role="link"]');
    $this->assertCssSelect('[data-webform-card="card_1"] .progress-title[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-title[role="link"]');
    $this->assertCssSelect('.progress-step.is-complete[data-webform-card="card_1"]');
    $this->assertCssSelect('.progress-step.is-active[data-webform-card="card_2"]');

    // Move to preview.
    $page->pressButton('edit-preview-next');
    $assert_session->waitForElement('css', '.webform-preview');
    $assert_session->waitForElement('css', 'input[type="submit"]#edit-webform-start-card_1');

    // Check that both cards are linked in the progress bar.
    $this->assertCssSelect('[data-webform-card="card_1"] .progress-marker[role="link"]');
    $this->assertCssSelect('[data-webform-card="card_1"] .progress-title[role="link"]');
    $this->assertCssSelect('[data-webform-card="card_2"] .progress-marker[role="link"]');
    $this->assertCssSelect('[data-webform-card="card_2"] .progress-title[role="link"]');
    $this->assertNoCssSelect('[data-webform-page="webform_preview"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-page="webform_preview"] .progress-title[role="link"]');
    $this->assertCssSelect('.progress-step.is-active[data-webform-page="webform_preview"]');

    // Check that both cards are linked in the preview.
    $this->assertCssSelect('input[type="submit"]#edit-webform-start-card_1');
    $this->assertCssSelect('input[type="submit"]#edit-webform-start-card_2');

    // Move back to card 1.
    $page->pressButton('edit-webform-start-card_1');
    $assert_session->waitForElement('css', '#edit-element-1');

    // Check that no cards are linked in the progress bar.
    $this->assertNoCssSelect('[data-webform-card="card_1"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_1"] .progress-title[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-marker[role="link"]');
    $this->assertNoCssSelect('[data-webform-card="card_2"] .progress-title[role="link"]');
    $this->assertCssSelect('.progress-step.is-active[data-webform-card="card_1"]');
  }

  /**
   * Passes if the query string on the current page is matched, fail otherwise.
   *
   * @param string $expected_query
   *   The expected query string.
   */
  protected function assertQuery($expected_query = ''): void {
    $actual_query = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_QUERY) ?: '';
    $this->assertEquals($expected_query, $actual_query);
  }

}
