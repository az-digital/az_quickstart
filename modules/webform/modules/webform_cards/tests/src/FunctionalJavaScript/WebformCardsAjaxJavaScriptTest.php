<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards ajax.
 *
 * @group webform_cards
 */
class WebformCardsAjaxJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test'];

  /**
   * Test webform cards ajax.
   */
  public function testAjax() {
    global $base_path;

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    // Get the webform and load card 1.
    $this->drupalGet('/webform/test_cards_ajax');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertCssSelect('[data-webform-card="card_1"].is-active');

    // Move to card 2.
    $page->pressButton('edit-cards-next');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $this->assertCssSelect('[data-webform-card="card_2"].is-active');

    // Move to preview.
    $page->pressButton('edit-preview-next');
    $assert_session->waitForElement('css', '.webform-preview');
    $this->assertCssSelect('[data-webform-page="webform_preview"].is-active');

    // Submit the form.
    $page->pressButton('Submit');
    $assert_session->waitForElement('css', '.webform-confirmation');
    $this->assertCssSelect('[data-webform-page="webform_confirmation"].is-active');

    // Confirm that the confirmation page is inline.
    $actual_path = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH) ?: '';
    $this->assertEquals($base_path . 'webform/test_cards_ajax', $actual_path);

    /* ********************************************************************** */

    // Get the webform and load card 1.
    $this->drupalGet('/webform/test_cards_ajax');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_1"]');
    $this->assertCssSelect('[data-webform-card="card_1"].is-active');

    // Move to card 2.
    $this->executeJqueryEvent('body', 'keydown', ['which' => 39]);
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="card_2"]');
    $this->assertCssSelect('[data-webform-card="card_2"].is-active');

    // Move to preview.
    $this->executeJqueryEvent('body', 'keydown', ['which' => 39]);
    $assert_session->waitForElement('css', '.webform-preview');
    $this->assertCssSelect('[data-webform-page="webform_preview"].is-active');

    // Submit the form.
    $this->executeJqueryEvent('body', 'keydown', ['which' => 39]);
    $assert_session->waitForElement('css', '.webform-confirmation');
    $this->assertCssSelect('[data-webform-page="webform_confirmation"].is-active');

    // Confirm that the confirmation page is inline.
    $actual_path = parse_url($this->getSession()->getCurrentUrl(), PHP_URL_PATH) ?: '';
    $this->assertEquals($base_path . 'webform/test_cards_ajax', $actual_path);
  }

}
