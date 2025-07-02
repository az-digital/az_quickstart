<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element description.
 *
 * @group Webform
 */
class WebformElementDescriptionTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_description'];

  /**
   * Test date element.
   */
  public function testDateElement() {
    $this->drupalGet('/webform/test_element_description');

    // Check .description class is included when description display is before.
    $this->assertCssSelect('.description #edit-description-before--description.webform-element-description');

    // Check .description class is included when description display is after.
    $this->assertCssSelect('.description #edit-description-after--description.webform-element-description');

    // Check tooltip classes are added to the element's wrapper.
    $this->assertCssSelect('.js-webform-tooltip-element.webform-tooltip-element.form-item-description-tooltip .webform-element-description.visually-hidden');

    // Check tooltip classes are NOT added when the element description is empty.
    $this->assertNoCssSelect('.js-webform-tooltip-element.webform-tooltip-element.form-item-description-tooltip-no-description');
    $this->assertCssSelect('.form-item-description-tooltip-no-description');

  }

}
