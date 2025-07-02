<?php

namespace Drupal\Tests\webform\FunctionalJavascript\Wizard;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform basic wizard.
 *
 * @group webform_javascript
 */
class WebformWizardBasicJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform basic wizard.
   */
  public function testBasicWizard() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_form_wizard_basic');

    /* ********************************************************************** */

    // Check page 1 URL.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $assert_session->responseContains('Element 1');
    $this->assertQuery();

    // Check page 2 URL.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery();

    // Enable tracking by name.
    $webform
      ->setSetting('wizard_track', 'name')
      ->save();

    // Check page 1 URL with ?page=*.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $assert_session->responseContains('Element 1');
    $this->assertQuery();

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery('page=page_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-wizard-prev');
    $assert_session->waitForText('Element 1');
    $this->assertQuery('page=page_1');

    // Check page 1 URL with custom param.
    $this->drupalGet('/webform/test_form_wizard_basic', ['query' => ['custom_param' => '1']]);
    $assert_session->responseContains('Element 1');
    $this->assertQuery('custom_param=1');

    // Check page 2 URL with ?page=2.
    $page->pressButton('edit-wizard-next');
    $assert_session->waitForText('Element 2');
    $this->assertQuery('custom_param=1&page=page_2');

    // Check page 1 URL with ?page=1.
    $page->pressButton('edit-wizard-prev');
    $assert_session->waitForText('Element 1');
    $this->assertQuery('custom_param=1&page=page_1');

    /* ********************************************************************** */

    // Set the webform to use ajax.
    $webform->setSetting('ajax', TRUE);
    $webform->save();

    // There should be no announcements when first visiting the form.
    $this->drupalGet('/webform/test_form_wizard_basic');
    $assert_session->responseContains('Element 1');
    $this->assertEquals('', $page->findById('drupal-live-announce')->getText());

    // Check announcements on next and previous pages.
    $page->pressButton('Next >');
    $assert_session->waitForText('"Test: Webform: Wizard basic: Page 2" loaded. (2 of 4)');

    $page->pressButton('< Previous');
    $assert_session->waitForText('"Test: Webform: Wizard basic: Page 1" loaded. (1 of 4)');

    $page->pressButton('Next >');
    $assert_session->waitForText('"Test: Webform: Wizard basic: Page 2" loaded. (2 of 4)');

    $page->pressButton('Preview');
    $assert_session->waitForText('"Test: Webform: Wizard basic: Preview" loaded. (3 of 4)');
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
