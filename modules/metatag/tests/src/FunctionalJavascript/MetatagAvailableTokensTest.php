<?php

namespace Drupal\Tests\metatag\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the available tokens under metatag routes.
 *
 * @group metatag
 */
class MetatagAvailableTokensTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['metatag', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test the node metatag defaults page.
   */
  public function testNodeMetatagDefaultsPage() {
    $this->drupalLogin($this->rootUser);
    $this->drupalGet(Url::fromRoute('entity.metatag_defaults.edit_form', ['metatag_defaults' => 'node']));
    $page = $this->getSession()->getPage();

    $token_dialog_link = $page->find('css', '.token-dialog');
    $token_dialog_link->click();

    // @todo This method exists on JsWebAssert() and is not available from the
    // assertSession() object?
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $assert_session->assertWaitOnAjaxRequest();

    $token_dialog = $page->find('css', '.token-tree-dialog');

    $token_node_token = $token_dialog->find('css', '#token-node');

    $this->assertNotEmpty($token_node_token);
  }

}
