<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for excluded columns element.
 *
 * @group webform
 */
class WebformElementExcludedColumnsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_excluded_columns'];

  /**
   * Test excluded columns element.
   */
  public function testExcludedElements() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_excluded_columns');

    $assert_session->fieldExists('webform_excluded_columns[tableselect][textfield]');
    $assert_session->fieldNotExists('webform_excluded_columns[tableselect][markup]');
    $assert_session->fieldNotExists('webform_excluded_columns[tableselect][details]');
  }

}
