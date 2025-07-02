<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards auto-forward.
 *
 * @group webform_cards
 */
class WebformCardsAutoForwardJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_cards', 'webform_cards_test', 'webform_image_select'];

  /**
   * Test webform cards auto-forward.
   */
  public function testAutoForward() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_cards_auto_forward');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="textfield"]');

    // Check that enter in textfield auto-forwards.
    $this->executeJqueryEvent('#edit-textfield', 'keydown', ['which' => 13]);
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="radios_example"]');

    // Check that radios auto-forwards.
    $session->executeScript('jQuery("#edit-radios-one").mouseup();');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="radios_other_example"]');

    // Check that clicking radios other 'Other…' does NOT auto-forward.
    $session->executeScript('jQuery("#edit-radios-other-radios-other-").mouseup();');
    $assert_session->waitForElement('css', '#edit-radios-other-other');
    $this->assertCssSelect('.webform-card--active[data-webform-key="radios_other_example"]');

    // Check that clicking radios other option does auto-forward.
    $session->executeScript('jQuery("#edit-radios-other-radios-one").mouseup();');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="scale"]');

    // Check that clicking scale does auto-forward.
    $session->executeScript('jQuery("#edit-scale-1").change();');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="rating"]');

    // Check that clicking rating does auto-forward.
    $session->executeScript("jQuery('#edit-rating').val('1').change()");
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="image_select"]');

    // Check that image select does auto-forward.
    $session->executeScript("jQuery('#edit-image-select').val('kitten_1').change()");
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="radios_multiple"]');

    // Check that clicking multiple radios does NOT auto-forward.
    $session->executeScript('jQuery("#edit-radios-multiple-1-one, #edit-radios-multiple-1-two").mouseup();');

    // Check that the form can be submitted.
    // @todo Determine why the below error is being thrown.
    // WebDriver\Exception\CurlExec: Curl error thrown for http POST
    // $page->pressButton('edit-submit');
    // $assert_session->pageTextContains('New submission added to Test: Webform: Cards auto-forward.');
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_cards_auto_forward_hide');

    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="radios_example"]');

    // Check that next button is hidden.
    $this->assertElementNotVisible('#edit-cards-prev');
    $this->assertElementNotVisible('#edit-cards-next');

    // Move to the next page.
    // Click the radio's label because radio is not visible.
    $this->click('label[for="edit-radios-one"]');
    $assert_session->waitForElement('css', '.webform-card--active[data-webform-key="radios_other_example"]');

    // Go back to previous page.
    $this->assertElementVisible('#edit-cards-prev');
    $this->assertElementNotVisible('#edit-cards-next');
    $page->pressButton('edit-cards-prev');
    $assert_session->waitForElementVisible('css', '#edit-cards-next');

    // Check that next button is now visible.
    $this->assertElementVisible('#edit-cards-next');
  }

}
