<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\FunctionalJavascript;

/**
 * Tests the confirm form link type.
 *
 * @group flag
 */
class LinkTypeConfirmFormTest extends FlagJsTestBase {

  /**
   * Test the confirm form link type.
   */
  public function testCreateConfirmFlag() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\FunctionalJavascriptTests\JSWebAssert $assert_session */
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet('/admin/structure/flags/add');
    $page->pressButton('Continue');
    $page->selectFieldOption('link_type', 'confirm');
    $assert_session->waitForText('Flag confirmation message');
    $assert_session->pageTextContains('Unflag confirmation message');
  }

}
