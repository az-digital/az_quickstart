<?php

namespace Drupal\Tests\webform_cards\FunctionalJavascript;

use Drupal\Tests\webform\FunctionalJavascript\WebformWebDriverTestBase;

/**
 * Tests for webform cards UI.
 *
 * @group webform_cards
 */
class WebformCardsUiJavaScriptTest extends WebformWebDriverTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'webform', 'webform_ui', 'webform_cards', 'webform_cards_test'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Test webform cards UI.
   */
  public function testUi() {
    $this->placeBlocks();

    $this->drupalLogin($this->rootUser);

    /* ********************************************************************** */

    // Check that 'Add page' and 'Add card' actions are visible.
    $this->drupalGet('/admin/structure/webform/manage/contact');
    $this->assertElementVisible('#webform-ui-add-page');
    $this->assertElementVisible('#webform-ui-add-card');

    // Check that only 'Add card' action is visible on cards form.
    $this->drupalGet('/admin/structure/webform/manage/test_cards_progress');
    $this->assertElementNotVisible('#webform-ui-add-page');
    $this->assertElementVisible('#webform-ui-add-card');

    // Check that only 'Add page' action is visible on wizard form.
    $this->drupalGet('/admin/structure/webform/manage/test_form_wizard_basic');
    $this->assertElementVisible('#webform-ui-add-page');
    $this->assertElementNotVisible('#webform-ui-add-card');
  }

}
