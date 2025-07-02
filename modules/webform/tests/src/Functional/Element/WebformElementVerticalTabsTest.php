<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for vertical tabs element.
 *
 * @group webform
 */
class WebformElementVerticalTabsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_vertical_tabs'];

  /**
   * Test vertical tabs element.
   */
  public function testVerticalTabs() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_vertical_tabs');

    // Check vertical_tabs element.
    $assert_session->responseContains('<div data-drupal-selector="edit-vertical-tabs" data-vertical-tabs-panes>');
    $assert_session->responseContains('<input class="vertical-tabs__active-tab" data-drupal-selector="edit-vertical-tabs-active-tab" type="hidden" name="vertical_tabs__active_tab" value="" />');

    // Check vertical_tabs advanced element.
    $assert_session->responseContains('<div data-drupal-selector="edit-vertical-tabs-advanced" aria-describedby="edit-vertical-tabs-advanced--description" data-vertical-tabs-panes>');
    $assert_session->responseContains('<input class="vertical-tabs__active-tab" data-drupal-selector="edit-vertical-tabs-advanced-active-tab" type="hidden" name="vertical_tabs_advanced__active_tab" value="edit-vertical-tabs-advanced-details-03" />');
  }

}
