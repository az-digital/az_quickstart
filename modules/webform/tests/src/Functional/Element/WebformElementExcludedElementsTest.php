<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for excluded elements element.
 *
 * @group webform
 */
class WebformElementExcludedElementsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_excluded_elements'];

  /**
   * Test excluded elements element.
   */
  public function testExcludedElements() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_excluded_elements');

    // Check markup is not listed via '#exclude_markup': TRUE.
    $assert_session->fieldNotExists('webform_excluded_elements[tableselect][markup]');

    // Check markup is listed via '#exclude_markup': FALSE.
    $assert_session->fieldExists('webform_excluded_elements_markup[tableselect][markup]');

    // Check composite sub element is listed via '#exclude_composite': TRUE.
    $assert_session->fieldNotExists('webform_excluded_elements[tableselect][telephone__type]');

    // Check composite sub element is listed via '#exclude_composite': FALSE.
    $assert_session->fieldExists('webform_excluded_elements_telephone[tableselect][telephone__type]');

    // Check composite sub element title is prepended with the element's title.
    $assert_session->responseContains('<td>Type</td>');
  }

}
